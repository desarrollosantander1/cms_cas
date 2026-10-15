<?php

namespace App\Service;

use App\Domain\CalculadorFechaVencimiento;
use App\Entity\Configuracion;
use App\Repository\ConfiguracionRepository;
use App\Service\Reporte\LayoutPlanoTxt;

/**
 * Puente entre datacredito.configuracion (Doctrine) y los sitios que antes
 * leían una constante de clase — mismo patrón que NovedadCatalogo: carga
 * todo el catálogo una sola vez por request y cachea por código. Falla
 * explícito si falta un código (nunca un null silencioso en la cabecera de
 * un plano regulatorio o en el cálculo de novedad).
 */
class ConfiguracionService
{
    /** @var array<string, Configuracion>|null codigo => Configuracion */
    private ?array $porCodigo = null;

    public function __construct(private readonly ConfiguracionRepository $configuraciones)
    {
    }

    public function valor(string $codigo): string
    {
        $configuracion = $this->todas()[$codigo] ?? null;
        if ($configuracion === null) {
            throw new \DomainException(sprintf('Configuración "%s" no está configurada en datacredito.configuracion.', $codigo));
        }

        return $configuracion->getValor();
    }

    // --- Nivel 1: plano TXT de DataCrédito ---

    public function codigoSuscriptor(): string
    {
        return $this->valor('REPORTE_CODIGO_SUSCRIPTOR');
    }

    public function tipoCuenta(): string
    {
        return $this->valor('REPORTE_TIPO_CUENTA');
    }

    public function oficinaRadicacion(): string
    {
        return $this->valor('REPORTE_OFICINA_RADICACION');
    }

    public function ciudadRadicacion(): string
    {
        return $this->valor('REPORTE_CIUDAD_RADICACION');
    }

    public function codigoDaneRadicacion(): string
    {
        return $this->valor('REPORTE_CODIGO_DANE_RADICACION');
    }

    public function correoDefecto(): string
    {
        return $this->valor('REPORTE_CORREO_DEFECTO');
    }

    /**
     * Layout físico completo del plano TXT de DataCrédito — único punto que
     * lo arma con los valores vigentes (mismo rol que calculadorFechaVencimiento()).
     * Cada llamador (hoy solo GeneradorTxtService) cachea la instancia
     * devuelta.
     */
    public function layoutPlanoTxt(): LayoutPlanoTxt
    {
        return new LayoutPlanoTxt(
            anchoLinea: (int) $this->valor('REPORTE_ANCHO_LINEA'),
            cabRegistroInicial: $this->valor('REPORTE_CAB_REGISTRO_INICIAL'),
            anchoFecha: (int) $this->valor('REPORTE_ANCHO_FECHA'),
            cabAmpliacionMilenio: $this->valor('REPORTE_CAB_AMPLIACION_MILENIO'),
            cabIndicadorValoresMiles: $this->valor('REPORTE_CAB_INDICADOR_VALORES_MILES'),
            cabTipoEntrega: $this->valor('REPORTE_CAB_TIPO_ENTREGA'),
            cabFechaInicioReporte: $this->valor('REPORTE_CAB_FECHA_INICIO_REPORTE'),
            cabFechaFinReporte: $this->valor('REPORTE_CAB_FECHA_FIN_REPORTE'),
            cabIndicadorPartir: $this->valor('REPORTE_CAB_INDICADOR_PARTIR'),
            cabAnchoFiller: (int) $this->valor('REPORTE_CAB_ANCHO_FILLER'),
            anchoTipoIdentificacion: (int) $this->valor('REPORTE_ANCHO_TIPO_IDENTIFICACION'),
            anchoNumeroIdentificacion: (int) $this->valor('REPORTE_ANCHO_NUMERO_IDENTIFICACION'),
            anchoNumeroCuentaObligacion: (int) $this->valor('REPORTE_ANCHO_NUMERO_CUENTA_OBLIGACION'),
            anchoNombreCompleto: (int) $this->valor('REPORTE_ANCHO_NOMBRE_COMPLETO'),
            largoMaximoNombreCompleto: (int) $this->valor('REPORTE_LARGO_MAXIMO_NOMBRE_COMPLETO'),
            anchoResponsableCalidadDeudor: (int) $this->valor('REPORTE_ANCHO_RESPONSABLE_CALIDAD_DEUDOR'),
            anchoNombreMarcaPrivacidad: (int) $this->valor('REPORTE_ANCHO_NOMBRE_MARCA_PRIVACIDAD'),
            anchoCalificacion: (int) $this->valor('REPORTE_ANCHO_CALIFICACION'),
            anchoProbabilidadIncumplimiento: (int) $this->valor('REPORTE_ANCHO_PROBABILIDAD_INCUMPLIMIENTO'),
            anchoEdadIncumplimiento: (int) $this->valor('REPORTE_ANCHO_EDAD_INCUMPLIMIENTO'),
            anchoValorMonetario: (int) $this->valor('REPORTE_ANCHO_VALOR_MONETARIO'),
            anchoCuotas: (int) $this->valor('REPORTE_ANCHO_CUOTAS'),
            anchoCiudad: (int) $this->valor('REPORTE_ANCHO_CIUDAD'),
            anchoCodigoDane: (int) $this->valor('REPORTE_ANCHO_CODIGO_DANE'),
            anchoDireccion: (int) $this->valor('REPORTE_ANCHO_DIRECCION'),
            anchoTelefono: (int) $this->valor('REPORTE_ANCHO_TELEFONO'),
            anchoCorreo: (int) $this->valor('REPORTE_ANCHO_CORREO'),
            anchoSuscriptorDestino: (int) $this->valor('REPORTE_ANCHO_SUSCRIPTOR_DESTINO'),
            anchoNumeroTarjeta: (int) $this->valor('REPORTE_ANCHO_NUMERO_TARJETA'),
            colaIdentificador: $this->valor('REPORTE_COLA_IDENTIFICADOR'),
            anchoNumeroRegistros: (int) $this->valor('REPORTE_ANCHO_NUMERO_REGISTROS'),
            anchoSumatoriaNovedades: (int) $this->valor('REPORTE_ANCHO_SUMATORIA_NOVEDADES'),
            colaAnchoFiller: (int) $this->valor('REPORTE_COLA_ANCHO_FILLER'),
        );
    }

    // --- Nivel 1: Generador de Cartas jurídicas ---

    public function cartasMensajePie(): string
    {
        return $this->valor('CARTAS_MENSAJE_PIE');
    }

    public function cartasAsuntoBase(): string
    {
        return $this->valor('CARTAS_ASUNTO_BASE');
    }

    public function cartasTituloCorreo(): string
    {
        return $this->valor('CARTAS_TITULO_CORREO');
    }

    /**
     * Textos editables del cuerpo de data_credito.html.twig. Algunos traen
     * un placeholder literal ({PLAZO_PAGO_DIAS}, {FECHA_MORA_DESDE}) que
     * ArmadorCartaService::textosCarta() resuelve con los datos de la
     * generación antes de pasarlos a Twig (ya como HTML, vía |raw).
     *
     * @return array<string, string> clave corta (sin prefijo CARTA_DATACREDITO_) => valor
     */
    public function textosCartaDataCredito(): array
    {
        return [
            'referencia' => $this->valor('CARTA_DATACREDITO_REFERENCIA'),
            'saludo' => $this->valor('CARTA_DATACREDITO_SALUDO'),
            'intro' => $this->valor('CARTA_DATACREDITO_INTRO'),
            'parrafoPlazo' => $this->valor('CARTA_DATACREDITO_PARRAFO_PLAZO'),
            'parrafoLey1266' => $this->valor('CARTA_DATACREDITO_PARRAFO_LEY1266'),
            'parrafoCumplimiento' => $this->valor('CARTA_DATACREDITO_PARRAFO_CUMPLIMIENTO'),
            'parrafoDinamica' => $this->valor('CARTA_DATACREDITO_PARRAFO_DINAMICA'),
            'cuentasBancarias' => $this->valor('CARTA_DATACREDITO_CUENTAS_BANCARIAS'),
            'contacto' => $this->valor('CARTA_DATACREDITO_CONTACTO'),
            'despedida' => $this->valor('CARTAS_DESPEDIDA'),
        ];
    }

    /**
     * Textos editables del cuerpo de prejuridico.html.twig (compartido por
     * prejurico-con-interes / prejurico-sin-interes). Placeholder
     * {EMAIL_ASESOR} resuelto por ArmadorCartaService::textosCarta().
     *
     * @return array<string, string> clave corta (sin prefijo CARTA_PREJURIDICO_) => valor
     */
    public function textosCartaPrejuridico(): array
    {
        return [
            'asunto' => $this->valor('CARTA_PREJURIDICO_ASUNTO'),
            'referencia' => $this->valor('CARTA_PREJURIDICO_REFERENCIA'),
            'saludo' => $this->valor('CARTA_PREJURIDICO_SALUDO'),
            'intro' => $this->valor('CARTA_PREJURIDICO_INTRO'),
            'parrafoPlazoLegal' => $this->valor('CARTA_PREJURIDICO_PARRAFO_PLAZO_LEGAL'),
            'cuentasBancarias' => $this->valor('CARTA_PREJURIDICO_CUENTAS_BANCARIAS'),
            'parrafoPago' => $this->valor('CARTA_PREJURIDICO_PARRAFO_PAGO'),
            'despedida' => $this->valor('CARTAS_DESPEDIDA'),
        ];
    }

    // --- Nivel 2: umbrales de dominio ---

    public function novedadUmbralMaterialidad(): float
    {
        return (float) $this->valor('NOVEDAD_UMBRAL_MATERIALIDAD');
    }

    public function novedadEdadMaximaDias(): int
    {
        return (int) $this->valor('NOVEDAD_EDAD_MAXIMA_DIAS');
    }

    /**
     * Único punto que arma CalculadorFechaVencimiento con los valores
     * vigentes — mismo rol que NovedadCatalogo::calculador() para
     * CalculadorNovedad. Cada llamador cachea la instancia devuelta (no es
     * responsabilidad de este servicio, que no sabe cuántas veces se pedirá
     * por request).
     */
    public function calculadorFechaVencimiento(): CalculadorFechaVencimiento
    {
        return new CalculadorFechaVencimiento(
            $this->vencimientoPlazosColapsados(),
            $this->vencimientoDiasGraciaEstandar(),
        );
    }

    /** @return int[] */
    private function vencimientoPlazosColapsados(): array
    {
        return array_map('intval', explode(',', $this->valor('VENCIMIENTO_PLAZOS_COLAPSADOS_DIAS')));
    }

    private function vencimientoDiasGraciaEstandar(): int
    {
        return (int) $this->valor('VENCIMIENTO_DIAS_GRACIA_ESTANDAR');
    }

    /** @return array<string, Configuracion> */
    private function todas(): array
    {
        if ($this->porCodigo === null) {
            $this->porCodigo = [];
            foreach ($this->configuraciones->findAll() as $configuracion) {
                $this->porCodigo[$configuracion->getCodigo()] = $configuracion;
            }
        }

        return $this->porCodigo;
    }
}
