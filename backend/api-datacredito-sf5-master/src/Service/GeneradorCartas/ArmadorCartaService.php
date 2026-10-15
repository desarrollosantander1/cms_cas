<?php

namespace App\Service\GeneradorCartas;

use App\Domain\CalculadorFechaVencimiento;
use App\Domain\CalculadorInteres;
use App\Entity\Gbl\Cliente;
use App\Entity\Gbl\Factura;
use App\Service\ConfiguracionService;
use NumberToWords\NumberToWords;

/**
 * Arma el contexto que consumen las plantillas Twig de las cartas jurídicas
 * (data_credito / prejuridico). Concentra las reglas de negocio portadas del
 * legacy (GeneradorCartasService::generarCartaCliente): fecha de vencimiento,
 * días de mora, interés a la tasa vigente (TasaInteresService), total a
 * pagar, sumatorias y monto en letras.
 *
 * Devuelve un array asociativo con claves en MAYÚSCULAS dentro de
 * `facturasSelect` / `cliente`, igual que el legacy, para que las plantillas
 * portadas casi no cambien.
 */
final class ArmadorCartaService
{
    private ?CalculadorFechaVencimiento $calculadorFechaVencimiento = null;

    public function __construct(
        private readonly ConfiguracionService $configuraciones,
        private readonly CalculadorInteres $calculadorInteres,
        private readonly TasaInteresService $tasaInteres,
    ) {
    }

    private function calculadorFechaVencimiento(): CalculadorFechaVencimiento
    {
        return $this->calculadorFechaVencimiento ??= $this->configuraciones->calculadorFechaVencimiento();
    }

    /**
     * @param Factura[]   $facturas             facturas seleccionadas (ya filtradas a las del payload)
     * @param float|null  $tasaMensualOverride  tasa ajustada por el usuario solo para esta generación (no
     *                                          persistida en BD); si es null se usa la tasa vigente en BD
     *
     * @return array<string, mixed>
     */
    public function armarContexto(
        TipoCartaJuridica $tipo,
        Cliente $cliente,
        array $facturas,
        ?int $paramsDays,
        string $emailAsesor,
        ?float $tasaMensualOverride = null,
    ): array {
        $hoy = new \DateTimeImmutable('today');
        $tasaMensual = $tasaMensualOverride ?? $this->tasaInteres->tasaVigente();

        $facturasSelect = [];
        $sumaConInteres = 0.0;
        $sumaSinInteres = 0.0;
        $fechaMasAntigua = null;

        foreach ($facturas as $factura) {
            $saldo = (float) $factura->getSaldoActual();
            $plazoPagoDias = $factura->getPlazoPagoDias() ?? 0;
            $fechaVencimiento = $this->calculadorFechaVencimiento()->calcular($factura->getFechaEmision(), $plazoPagoDias);

            $diasMoraBase = max(0, (int) floor(($hoy->getTimestamp() - $fechaVencimiento->getTimestamp()) / 86400));
            $diasMora = $tipo->admiteParamsDays() && $paramsDays !== null
                ? $diasMoraBase + $paramsDays
                : $diasMoraBase;

            $interes = $this->calculadorInteres->calcular($saldo, $diasMora, $tasaMensual);
            $totalAPagar = $tipo->totalConInteres() ? $saldo + $interes : $saldo;

            $sumaConInteres += $saldo + $interes;
            $sumaSinInteres += $saldo;

            if ($fechaMasAntigua === null || $fechaVencimiento < $fechaMasAntigua) {
                $fechaMasAntigua = $fechaVencimiento;
            }

            $facturasSelect[] = [
                'COMPROBANTE' => $factura->getComprobante().'-'.$factura->getNumero(),
                'FECHA_FACTURA' => $factura->getFechaEmision()->format('d-m-Y'),
                'FECHA_VENCIMIENTO' => $fechaVencimiento->format('d-m-Y'),
                'VALOR_INICIAL' => (float) $factura->getTotal(),
                'SALDO' => $saldo,
                'DIAS_MORA' => $diasMora,
                'INTERES' => $interes,
                'TOTAL_A_PAGAR' => $totalAPagar,
            ];
        }

        $sumatoria = $tipo->totalConInteres() ? $sumaConInteres : $sumaSinInteres;

        return [
            'cliente' => $this->contextoCliente($cliente, $facturas, $facturasSelect),
            'facturasSelect' => $facturasSelect,
            'fechaMasAntigua' => $fechaMasAntigua,
            'sumatoriaCarteraTexto' => $this->montoEnLetras($sumatoria),
            'sumatoriaCarteraNumber' => number_format($sumatoria, 0, ',', '.'),
            'estadoCuentaInteres' => $tipo->muestraInteres() ? 1 : 0,
            'textos' => $this->textosCarta($tipo, $this->plazoPagoDerivado($facturas), $fechaMasAntigua, $emailAsesor),
        ];
    }

    /**
     * Textos editables del cuerpo de la carta (datacredito.configuracion,
     * ver ConfiguracionService::textosCartaDataCredito()/textosCartaPrejuridico())
     * con los placeholders {PLAZO_PAGO_DIAS} / {FECHA_MORA_DESDE} /
     * {EMAIL_ASESOR} ya resueltos. Las plantillas los imprimen con |raw
     * (pueden traer HTML simple: <b>, <br>, <mark>), igual que CARTAS_MENSAJE_PIE
     * en el correo.
     *
     * @return array<string, string>
     */
    private function textosCarta(TipoCartaJuridica $tipo, int $plazoPagoDias, ?\DateTimeImmutable $fechaMasAntigua, string $emailAsesor): array
    {
        $reemplazos = [
            '{PLAZO_PAGO_DIAS}' => (string) $plazoPagoDias,
            '{FECHA_MORA_DESDE}' => $fechaMasAntigua !== null ? $this->fechaLarga($fechaMasAntigua) : '',
            '{EMAIL_ASESOR}' => $emailAsesor,
        ];

        $textos = match ($tipo) {
            TipoCartaJuridica::DATA_CREDITO => $this->configuraciones->textosCartaDataCredito(),
            TipoCartaJuridica::PREJURIDICO_CON_INTERES, TipoCartaJuridica::PREJURIDICO_SIN_INTERES => $this->configuraciones->textosCartaPrejuridico(),
        };

        return array_map(static fn (string $texto): string => strtr($texto, $reemplazos), $textos);
    }

    /** "18 de Septiembre de 2026" — mismo formato que el macro fechaLarga de _macros.html.twig, resuelto en PHP para poder incrustarlo en un texto que viene de BD antes de llegar a Twig. */
    private function fechaLarga(\DateTimeImmutable $fecha): string
    {
        static $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return sprintf('%d de %s de %s', (int) $fecha->format('j'), $meses[(int) $fecha->format('n')], $fecha->format('Y'));
    }

    /**
     * Ciudad/departamento/dirección salen de la sucursal de la primera
     * factura seleccionada (mismo criterio del plan rev 2). PLAZO_PAGO se
     * deriva de las facturas: el plazo más frecuente; a igualdad, el mayor.
     *
     * @param Factura[]                    $facturas
     * @param array<int, array<string, mixed>> $facturasSelect
     *
     * @return array<string, mixed>
     */
    private function contextoCliente(Cliente $cliente, array $facturas, array $facturasSelect): array
    {
        $sucursal = $facturas[array_key_first($facturas)]->getClienteSucursal();

        return [
            'RAZON_SOCIAL' => $cliente->getNombreMostrar(),
            'NUMERO_IDENTIDAD' => $cliente->getNumeroIdentidad(),
            'CIUDAD' => $sucursal->getCiudadNombre() ?? '',
            'DEPARTAMENTO' => $sucursal->getDepartamentoNombre() ?? '',
            'DIRECCION' => $this->normalizarEspacios($sucursal->getDireccion() ?? ''),
            'PLAZO_PAGO' => $this->plazoPagoDerivado($facturas),
        ];
    }

    /**
     * @param Factura[] $facturas
     */
    private function plazoPagoDerivado(array $facturas): int
    {
        $conteo = [];
        foreach ($facturas as $factura) {
            $plazo = $factura->getPlazoPagoDias() ?? 0;
            $conteo[$plazo] = ($conteo[$plazo] ?? 0) + 1;
        }

        if ($conteo === []) {
            return 0;
        }

        // Más frecuente; a igualdad de frecuencia, el plazo mayor.
        $mejorPlazo = 0;
        $mejorConteo = -1;
        foreach ($conteo as $plazo => $veces) {
            if ($veces > $mejorConteo || ($veces === $mejorConteo && $plazo > $mejorPlazo)) {
                $mejorPlazo = $plazo;
                $mejorConteo = $veces;
            }
        }

        return $mejorPlazo;
    }

    private function normalizarEspacios(string $texto): string
    {
        return trim((string) preg_replace('/\s+/', ' ', str_replace(["\t", "\n", "\r"], ' ', $texto)));
    }

    /** "Un millón doscientos mil pesos" — igual que el legacy (ucfirst + " pesos"). */
    private function montoEnLetras(float $monto): string
    {
        $transformer = (new NumberToWords())->getNumberTransformer('es');

        return ucfirst($transformer->toWords((int) round($monto))).' pesos';
    }
}
