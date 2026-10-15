<?php

namespace App\Service\Reporte;

use App\Domain\CalculadorFechaVencimiento;
use App\Entity\Gbl\Factura;
use App\Entity\PeriodoCierre;
use App\Entity\ReporteFactura;
use App\Service\ConfiguracionService;

/**
 * Genera el plano de ancho fijo que se entrega a DataCrédito: una línea de
 * cabecera, una línea por cada factura reportable, una línea de cola —
 * cada línea de exactamente 800 caracteres, terminadas en PHP_EOL. Porta
 * DataCreditoService::obligacionesCierreMesCabecera() y
 * DataCreditoRepository::obligacionesCierreMes()/obligacionesCierreMesFinal()
 * del legacy (SQL de ancho fijo con LPAD/RPAD) a PHP con constantes
 * nombradas — layout confirmado leyendo el SQL original campo por campo,
 * verificando que cabecera/cuerpo/cola sí suman 800 caracteres cada una.
 *
 * Estrictamente de solo lectura: recibe entidades ya persistidas
 * (`ReporteFactura`, snapshot congelado — ver su propio docblock) y no
 * hace ningún `persist()`/`flush()`. No confundir con una previsualización
 * de novedades sin guardar — ver `PeriodoFacturaService::previsualizarFacturas()`
 * para eso (corrige el bug legacy #5: preview con efectos secundarios).
 *
 * Varios campos del legacy dependían de CRM_CARTERA (`cc2`), una tabla de
 * "cartera"/cuotas que este diseño eliminó por completo (ver docblock de
 * `Factura`: `saldo_actual` ya viene resuelto de NetSuite, sin reconstruir
 * cartera). Donde el legacy hacía `COALESCE(cc2.TOTAL, cf.TOTAL)`, aquí solo
 * existe `cf.TOTAL` (`Factura::getTotal()`) — la rama `cc2.TOTAL` nunca
 * puede resolver en este modelo, así que el `COALESCE` colapsa siempre a su
 * segunda rama. Documentado en cada campo que lo usa, no oculto.
 */
final class GeneradorTxtService
{
    // Anchos de campo, literales fijos de cabecera/cola y fillers: todos en
    // datacredito.configuracion (prefijo REPORTE_*), resueltos como un solo
    // objeto — ver LayoutPlanoTxt / ConfiguracionService::layoutPlanoTxt().
    // CODIGO_SUSCRIPTOR/TIPO_CUENTA: idem, resueltos aparte (ConfiguracionService::codigoSuscriptor()/tipoCuenta()).

    private ?CalculadorFechaVencimiento $calculadorFechaVencimiento = null;

    private ?LayoutPlanoTxt $layout = null;

    public function __construct(
        private readonly ConfiguracionService $configuraciones,
    ) {
    }

    /** Fuente única del nombre de archivo del TXT — antes duplicado (y desincronizado) en ReporteController. */
    public function nombreArchivo(\DateTimeImmutable $fechaReporte): string
    {
        return sprintf('PTCMZ%s%s.txt', $this->configuraciones->codigoSuscriptor(), $fechaReporte->format('Ym'));
    }

    private function calculadorFechaVencimiento(): CalculadorFechaVencimiento
    {
        return $this->calculadorFechaVencimiento ??= $this->configuraciones->calculadorFechaVencimiento();
    }

    private function layout(): LayoutPlanoTxt
    {
        return $this->layout ??= $this->configuraciones->layoutPlanoTxt();
    }

    /** @param ReporteFactura[] $reportesFactura */
    public function generar(PeriodoCierre $periodo, array $reportesFactura): string
    {
        $filas = array_values(array_filter(
            $reportesFactura,
            static fn (ReporteFactura $rf): bool => $rf->isReportar(),
        ));

        $lineas = [$this->cabecera($periodo->getFechaReporte())];

        $sumatoriaNovedades = 0;
        foreach ($filas as $rf) {
            $lineas[] = $this->cuerpo($rf, $periodo->getFechaReporte());
            $sumatoriaNovedades += $rf->getNovedad()->getCodigo();
        }

        $lineas[] = $this->cola($periodo->getFechaReporte(), count($filas) + 2, $sumatoriaNovedades);

        return implode(PHP_EOL, $lineas).PHP_EOL;
    }

    private function cabecera(\DateTimeImmutable $fechaReporte): string
    {
        return $this->exigirAncho(implode('', $this->camposCabecera($fechaReporte)), 'cabecera');
    }

    /**
     * Los campos de la línea de cabecera, ya formateados pero sin concatenar
     * — fuente compartida con GeneradorPlanoExcelService, que necesita
     * escribirla como una fila propia (no una fila de factura) para que el
     * Excel reproduzca la misma mezcla de tipos de registro (cabecera/
     * cuerpo/cola) que exige el sistema externo de centrales de riesgo al
     * que se sube este archivo — ver docblock de esa clase.
     *
     * @return array<string, string>
     */
    public function camposCabecera(\DateTimeImmutable $fechaReporte): array
    {
        $layout = $this->layout();

        return [
            'REGISTRO_INICIAL'        => $layout->cabRegistroInicial,
            'CODIGO_SUSCRIPTOR'       => $this->configuraciones->codigoSuscriptor(),
            'TIPO_CUENTA'             => $this->configuraciones->tipoCuenta(),
            'FECHA_CORTE'             => $fechaReporte->format('Ymd'),
            'AMPLIACION_MILENIO'      => $layout->cabAmpliacionMilenio,
            'INDICADOR_VALORES_MILES' => $layout->cabIndicadorValoresMiles,
            'TIPO_ENTREGA'            => $layout->cabTipoEntrega,
            'FECHA_INICIO_REPORTE'    => $layout->cabFechaInicioReporte,
            'FECHA_FIN_REPORTE'       => $layout->cabFechaFinReporte,
            'INDICADOR_PARTIR'        => $layout->cabIndicadorPartir,
            'FILLER'                  => str_repeat('0', $layout->cabAnchoFiller),
        ];
    }

    private function cuerpo(ReporteFactura $rf, \DateTimeImmutable $fechaReporte): string
    {
        $campos = $this->camposCuerpoBase($rf, $fechaReporte);

        return $this->exigirAncho(implode('', $campos), sprintf('cuerpo (factura %s)', $rf->getFactura()->getNumeroComprobante()));
    }

    /**
     * Construye los 69 campos de una fila (los 3 bloques de dirección
     * todavía compuestos, sin expandir — ver camposDeFila() para eso).
     * Único punto de la regla de negocio por campo; `cuerpo()` solo
     * concatena, `camposDeFila()` solo expande los bloques de dirección.
     *
     * @return array<string, string>
     */
    private function camposCuerpoBase(ReporteFactura $rf, \DateTimeImmutable $fechaReporte): array
    {
        $factura = $rf->getFactura();
        $cliente = $rf->getCliente();
        $sucursal = $factura->getClienteSucursal();
        $novedad = $rf->getNovedad()->getCodigo();
        $layout = $this->layout();

        $fechaVencimiento = $this->calculadorFechaVencimiento()->calcular($factura->getFechaEmision(), $factura->getPlazoPagoDias() ?? 0);

        // Bloque de contacto: el legacy repite la misma dirección 3 veces
        // (residencia/laboral/correspondencia) porque solo hay una
        // dirección por sucursal — no se inventa una distinción que no
        // existe en los datos. OJO: el bloque de correspondencia NO incluye
        // teléfono (a diferencia de residencia/laboral) — confirmado
        // contando el SQL legacy campo por campo, no es un descuido.
        $direccionCiudadDepto =
            $this->rpad($this->normalizarTexto($sucursal->getCiudadNombre() ?? ''), $layout->anchoCiudad).
            $this->rpad($sucursal->getCiudadCodigoDane() ?? '', $layout->anchoCodigoDane, '0').
            $this->rpad($this->normalizarTexto($sucursal->getDepartamentoNombre() ?? ''), $layout->anchoCiudad).
            $this->rpad($this->normalizarTexto($sucursal->getDireccion() ?? ''), $layout->anchoDireccion);
        $telefono = $this->lpad(trim($sucursal->getTelefono() ?? ''), $layout->anchoTelefono);

        $bloqueConTelefono = $direccionCiudadDepto.$telefono; // residencia y laboral
        $bloqueSinTelefono = $direccionCiudadDepto; // correspondencia

        // CUPO_APROBADO/VALOR_CUOTA_MENSUAL: legacy = COALESCE(cc2.TOTAL, cf.TOTAL)
        // — cc2 (CRM_CARTERA) no existe en este modelo, colapsa siempre a
        // cf.TOTAL (Factura::getTotal()).
        $totalFactura = (string) round((float) $factura->getTotal());

        return [
            'TIPO_IDENTIFICACION'         => substr($cliente->getTipoIdentificacion()?->getCodigo() ?? '0', 0, $layout->anchoTipoIdentificacion),
            'NUMERO_IDENTIFICACION'       => $this->lpad(trim($cliente->getNumeroIdentidad()), $layout->anchoNumeroIdentificacion),
            'NUMERO_CUENTA_OBLIGACION'    => $this->lpad($factura->getNumeroComprobante(), $layout->anchoNumeroCuentaObligacion),
            'NOMBRE_COMPLETO'             => $this->rpad($this->normalizarNombre($cliente->getNombreMostrar()), $layout->anchoNombreCompleto),
            'SITUACION_TITULAR'           => '0',
            'FECHA_APERTURA'              => $this->lpad($factura->getFechaEmision()->format('Ymd'), $layout->anchoFecha),
            'FECHA_VENCIMIENTO'           => $this->lpad($fechaVencimiento->format('Ymd'), $layout->anchoFecha),
            'RESPONSABLE_CALIDAD_DEUDOR'  => $this->rpad('00', $layout->anchoResponsableCalidadDeudor, '0'),
            'TIPO_OBLIGACION'             => '1', // sobregiro(1)/tarjeta de crédito(2)
            'SUBSIDIO_HIPOTECARIO'        => '0', // solo aplica a tipo_obligacion=3
            'FECHA_SUBSIDIO'              => '00000000',
            'TERMINO_CONTRATO_OBLIGACION' => '2',
            'FORMA_PAGO'                  => $this->formaPago($novedad),
            'PERIOCIDAD_PAGO'             => '1',
            'NOVEDAD'                     => $this->lpad((string) $novedad, 2), // cada rama del CASE legacy es solo el código zero-padded
            'SITUACION_CARTERA'           => '0',
            'FECHA_SITUACION_CARTERA'     => $this->lpad($factura->getFechaEmision()->format('Ymd'), $layout->anchoFecha),
            'ESTADO_CUENTA'               => $this->estadoCuenta($novedad),
            'FECHA_ESTADO_CUENTA'         => $this->lpad($fechaReporte->format('Ymd'), $layout->anchoFecha), // siempre la fecha de corte del período
            'ESTADO_PLASTICO'             => '0', // solo aplica a tarjeta de crédito
            'FECHA_ESTADO_PLASTICO'       => '00000000',
            'ADJETIVO'                    => '0',
            'FECHA_ADJETIVO'              => '00000000',
            'CLASE_TARJETA'               => '0',
            'FRANQUICIA'                  => '0',
            'NOMBRE_MARCA_PRIVACIDAD'     => $this->rpad('', $layout->anchoNombreMarcaPrivacidad),
            'TIPO_MONEDA'                 => '1',
            'TIPO_GARANTIA'               => '1',
            // A diferencia del resto de campos de texto, DataCrédito espera CALIFICACION
            // alineada a la derecha con relleno de espacio a la izquierda (" A", " D") —
            // confirmado comparando contra un plano real de heos4, que siempre lo rellena así.
            'CALIFICACION'                  => $this->lpad($this->calificacion($novedad), $layout->anchoCalificacion, ' '),
            'PROBABILIDAD_INCUMPLIMIENTO'   => '000',
            'EDAD_INCUMPLIMIENTO'           => $this->lpad((string) $this->edadIncumplimiento($novedad, $rf->getDiasVencidos()), $layout->anchoEdadIncumplimiento),
            'CUPO_APROBADO'                 => $this->lpad($totalFactura, $layout->anchoValorMonetario),
            'CUPO_UTILIZADO'                => $this->lpad((string) $this->cupoUtilizado($novedad, $rf->getSaldo()), $layout->anchoValorMonetario),
            'VALOR_DISPONIBLE'              => str_repeat('0', $layout->anchoValorMonetario),
            'VALOR_CUOTA_MENSUAL'           => $this->lpad($totalFactura, $layout->anchoValorMonetario),
            'VALOR_SALDO_MORA'              => $this->lpad((string) $this->valorSaldoMora($novedad, $rf->getSaldo()), $layout->anchoValorMonetario),
            'TOTAL_CUOTAS'                  => $this->lpad('1', $layout->anchoCuotas),
            'CUOTAS_CANCELADAS'             => $this->lpad((string) ($novedad === 5 ? 1 : 0), $layout->anchoCuotas),
            'CUOTAS_MORA'                   => $this->lpad((string) (\in_array($novedad, [1, 5], true) ? 0 : 1), $layout->anchoCuotas),
            'CLAUSULA_PERMANENCIA'          => '000',
            'FECHA_CLAUSULA_PERMANENCIA'    => '00000000',
            'FECHA_LIMITE_PAGO'             => $this->lpad($this->fechaLimitePago($novedad, $fechaReporte, $fechaVencimiento), $layout->anchoFecha),
            'FECHA_PAGO'                    => $this->lpad($this->fechaPago($novedad, $fechaReporte, $fechaVencimiento), $layout->anchoFecha),
            'OFICINA_RADICACION'            => $this->rpad($this->configuraciones->oficinaRadicacion(), $layout->anchoNombreMarcaPrivacidad),
            'CIUDAD_RADICACION'             => $this->rpad($this->configuraciones->ciudadRadicacion(), $layout->anchoCiudad),
            'CODIGO_DANE_CIUDAD_RADICACION' => $this->rpad($this->configuraciones->codigoDaneRadicacion(), $layout->anchoCodigoDane),
            'DIRECCION_RESIDENCIA_BLOQUE'   => $bloqueConTelefono, // CIUDAD/CODIGO_DANE/DEPARTAMENTO/DIRECCION/TELEFONO_RESIDENCIA
            'DIRECCION_LABORAL_BLOQUE'      => $bloqueConTelefono, // ídem — el legacy repite la misma dirección
            'DIRECCION_CORRESPONDENCIA_BLOQUE' => $bloqueSinTelefono, // ídem, sin teléfono
            'CORREO_ELECTRONICO'               => $this->rpad(trim($sucursal->getCorreo() ?? $this->configuraciones->correoDefecto()), $layout->anchoCorreo),
            'CELULAR'                          => $this->lpad(trim($sucursal->getCelular() ?? '0'), $layout->anchoTelefono),
            'SUSCRIPTOR_DESTINO'               => $this->configuraciones->codigoSuscriptor(), // mismo código que CODIGO_SUSCRIPTOR
            'NUMERO_TARJETA'                   => $this->lpad('0', $layout->anchoNumeroTarjeta),
            'DETALLE_GARANTIA'                 => ' ',
            'INTERNO_EXPERIAN'                 => '0',
            'FECHA_EXPEDICION_DOCUMENTO'       => '00000000',
            'LUGAR_EXPEDICION_DOCUMENTO'       => '00000000',
            'ESPACIO_BLANCO'                   => ' ',
        ];
    }

    /**
     * Los campos de una fila del cuerpo, ya formateados/rellenados pero SIN
     * concatenar — fuente compartida para el TXT (`cuerpo()`, que solo
     * concatena) y para GeneradorPlanoExcelService (una columna por campo).
     * Los 3 bloques de dirección (residencia/laboral/correspondencia) se
     * exponen ya expandidos a sus columnas individuales para que el Excel
     * "plano" muestre exactamente las mismas 69 columnas que documenta el
     * formato, no 3 campos compuestos.
     *
     * @return array<string, string>
     */
    public function camposDeFila(ReporteFactura $rf, \DateTimeImmutable $fechaReporte): array
    {
        $factura = $rf->getFactura();
        $sucursal = $factura->getClienteSucursal();
        $layout = $this->layout();

        $ciudad = $this->rpad($this->normalizarTexto($sucursal->getCiudadNombre() ?? ''), $layout->anchoCiudad);
        $codigoDane = $this->rpad($sucursal->getCiudadCodigoDane() ?? '', $layout->anchoCodigoDane, '0');
        $departamento = $this->rpad($this->normalizarTexto($sucursal->getDepartamentoNombre() ?? ''), $layout->anchoCiudad);
        $direccion = $this->rpad($this->normalizarTexto($sucursal->getDireccion() ?? ''), $layout->anchoDireccion);
        $telefono = $this->lpad(trim($sucursal->getTelefono() ?? ''), $layout->anchoTelefono);

        $campos = $this->camposCuerpoBase($rf, $fechaReporte);

        $expandido = [];
        foreach ($campos as $nombre => $valor) {
            $expandido = match ($nombre) {
                'DIRECCION_RESIDENCIA_BLOQUE' => $expandido + [
                    'CIUDAD_RESIDENCIA' => $ciudad, 'CODIGO_DANE_CIUDAD_RESIDENCIA' => $codigoDane,
                    'DEPARTAMENTO_RESIDENCIA' => $departamento, 'DIRECCION_RESIDENCIA' => $direccion, 'TELEFONO_RESIDENCIA' => $telefono,
                ],
                'DIRECCION_LABORAL_BLOQUE' => $expandido + [
                    'CIUDAD_LABORAL' => $ciudad, 'CODIGO_DANE_CIUDAD_LABORAL' => $codigoDane,
                    'DEPARTAMENTO_LABORAL' => $departamento, 'DIRECCION_LABORAL' => $direccion, 'TELEFONO_LABORAL' => $telefono,
                ],
                'DIRECCION_CORRESPONDENCIA_BLOQUE' => $expandido + [
                    'CIUDAD_CORRESPONDENCIA' => $ciudad, 'CODIGO_CIUDAD_CORRESPONDENCIA' => $codigoDane,
                    'DEPARTAMENTO_CORRESPONDENCIA' => $departamento, 'DIRECCION_CORRESPONDENCIA' => $direccion,
                ],
                default => $expandido + [$nombre => $valor],
            };
        }

        return $expandido;
    }

    private function cola(\DateTimeImmutable $fechaReporte, int $numeroRegistros, int $sumatoriaNovedades): string
    {
        return $this->exigirAncho(implode('', $this->camposCola($fechaReporte, $numeroRegistros, $sumatoriaNovedades)), 'cola');
    }

    /**
     * Los campos de la línea de cola, ya formateados pero sin concatenar —
     * misma razón de ser que camposCabecera(): fuente compartida con
     * GeneradorPlanoExcelService.
     *
     * @return array<string, string>
     */
    public function camposCola(\DateTimeImmutable $fechaReporte, int $numeroRegistros, int $sumatoriaNovedades): array
    {
        $layout = $this->layout();

        return [
            'IDENTIFICADOR' => $layout->colaIdentificador,
            'FECHA' => $fechaReporte->format('Ymd'),
            'NUMERO_REGISTROS' => $this->lpad((string) $numeroRegistros, $layout->anchoNumeroRegistros),
            'SUMATORIA_NOVEDADES' => $this->lpad((string) $sumatoriaNovedades, $layout->anchoSumatoriaNovedades),
            'FILLER' => str_repeat('0', $layout->colaAnchoFiller),
        ];
    }

    // --- Reglas de negocio por novedad (porta el CASE del SQL legacy) ---

    private function formaPago(int $novedad): string
    {
        return \in_array($novedad, [12, 13], true) ? '0' : '1';
    }

    private function estadoCuenta(int $novedad): string
    {
        $codigo = match (true) {
            $novedad === 1 => '01',
            $novedad === 9 => '06',
            \in_array($novedad, [6, 7, 8], true) => '02',
            $novedad === 5 => '03',
            $novedad === 12 => '05',
            $novedad === 13 => '06',
            default => throw new \DomainException(sprintf('Novedad %d sin ESTADO_CUENTA definido.', $novedad)),
        };

        return $this->rpad($codigo, $this->layout()->anchoResponsableCalidadDeudor, '0');
    }

    private function calificacion(int $novedad): string
    {
        return match (true) {
            \in_array($novedad, [1, 5], true) => 'A',
            \in_array($novedad, [6, 7], true) => 'B',
            $novedad === 8 => 'C',
            \in_array($novedad, [9, 12, 13], true) => 'D',
            default => throw new \DomainException(sprintf('Novedad %d sin CALIFICACION definida.', $novedad)),
        };
    }

    /**
     * El legacy calcula esto restando dos strings YYYYMMDD como si fueran
     * números (`TO_CHAR(fecha_vencimiento,'YYYYMMDD') - fecha_corte`), lo
     * cual NO es una diferencia de días real cuando cruza mes/año (ej.
     * 20260301 - 20260228 = 73, no 1). Se usa aquí el `diasVencidos` ya
     * congelado en `ReporteFactura` — la misma magnitud que el legacy
     * pretendía calcular, pero correcta.
     */
    private function edadIncumplimiento(int $novedad, int $diasVencidos): int
    {
        return \in_array($novedad, [1, 5], true) ? 0 : $diasVencidos;
    }

    private function cupoUtilizado(int $novedad, string $saldo): int
    {
        return $novedad === 5 ? 0 : (int) round((float) $saldo);
    }

    private function valorSaldoMora(int $novedad, string $saldo): int
    {
        return \in_array($novedad, [1, 5], true) ? 0 : (int) round((float) $saldo);
    }

    private function fechaLimitePago(int $novedad, \DateTimeImmutable $fechaReporte, \DateTimeImmutable $fechaVencimiento): string
    {
        return \in_array($novedad, [1, 5], true) ? $fechaReporte->format('Ymd') : $fechaVencimiento->format('Ymd');
    }

    private function fechaPago(int $novedad, \DateTimeImmutable $fechaReporte, \DateTimeImmutable $fechaVencimiento): string
    {
        return match (true) {
            $novedad === 1 => $fechaReporte->format('Ymd'),
            $novedad === 5 => $fechaVencimiento->format('Ymd'),
            default => '00000000',
        };
    }

    // --- Normalización de texto ---

    /**
     * Solo para NOMBRE_COMPLETO: strip de acentos españoles básicos + '&'→'Y'
     * + mayúsculas + elimina TODO espacio/punto (no los colapsa, los borra —
     * fiel al `REGEXP_REPLACE('[\s.]+','')` del legacy).
     */
    private function normalizarNombre(string $valor): string
    {
        $sinAcentos = strtr($valor, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'n',
        ]);
        $mayusculas = mb_strtoupper($sinAcentos);
        $sinAmpersand = str_replace('&', 'Y', $mayusculas);
        $sinEspacios = preg_replace('/[\s.]+/', '', $sinAmpersand) ?? $sinAmpersand;

        return mb_substr($sinEspacios, 0, $this->layout()->largoMaximoNombreCompleto);
    }

    /**
     * Para ciudad/departamento/dirección: strip de acentos (juego más amplio
     * que normalizarNombre — incluye grave/diéresis/cedilla) + elimina todo
     * lo que no sea alfanumérico o espacio. El legacy tenía aquí un TRANSLATE
     * con el string origen más largo que el destino (bug de Oracle: los
     * caracteres sobrantes se borraban silenciosamente o mapeaban mal, ej.
     * 'à' terminaba mapeado a 'U'). Se corrige aquí a un mapeo correcto —
     * reproducir el bug no tiene ningún propósito de negocio. A diferencia
     * de normalizarNombre, NO pasa a mayúsculas (el legacy tampoco lo hacía
     * para estos campos).
     */
    private function normalizarTexto(string $valor): string
    {
        $sinAcentos = strtr($valor, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ç' => 'c', 'Ç' => 'C',
        ]);

        return preg_replace('/[^A-Za-z0-9 ]/', '', $sinAcentos) ?? $sinAcentos;
    }

    // --- Padding ---

    private function lpad(string $valor, int $ancho, string $relleno = '0'): string
    {
        return str_pad(mb_substr($valor, 0, $ancho), $ancho, $relleno, STR_PAD_LEFT);
    }

    private function rpad(string $valor, int $ancho, string $relleno = ' '): string
    {
        return str_pad(mb_substr($valor, 0, $ancho), $ancho, $relleno, STR_PAD_RIGHT);
    }

    private function exigirAncho(string $linea, string $descripcion): string
    {
        $anchoEsperado = $this->layout()->anchoLinea;
        $ancho = mb_strlen($linea);
        if ($ancho !== $anchoEsperado) {
            throw new \DomainException(sprintf('Línea "%s" tiene %d caracteres, se esperaban %d.', $descripcion, $ancho, $anchoEsperado));
        }

        return $linea;
    }
}
