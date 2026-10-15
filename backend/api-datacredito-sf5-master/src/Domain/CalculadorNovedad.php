<?php

namespace App\Domain;

/**
 * Regla de negocio "novedad" (código de estado que exige DataCrédito),
 * portada de DataCreditoService::obtenerNovedad() del módulo legacy —
 * ahí solo existía como CASE/if sueltos en PHP con enteros mágicos.
 * Clase de dominio pura: sin Doctrine ni Symfony, testeable sin DB/kernel.
 *
 * Precedencia (de mayor a menor): pagada/castigada (5) > cobro jurídico
 * (12/13) > rango de días vencidos (1/6/7/8/9, data-driven vía $rangos).
 *
 * $umbralMaterialidad/$edadMaximaDias antes vivían como constantes públicas
 * de clase; ahora se reciben por constructor (defaults = mismos valores de
 * siempre) para que datacredito.configuracion pueda fijarlos sin romper la
 * pureza de esta clase — ver NovedadCatalogo::calculador(), único punto que
 * la arma con los valores vigentes.
 */
final class CalculadorNovedad
{
    private const UMBRAL_MATERIALIDAD_DEFECTO = 10000;

    private const EDAD_MAXIMA_DIAS_DEFECTO = 2920;

    /**
     * @param RangoDiasNovedad[] $rangos rangos de días activos (es_manual = false) del catálogo `novedad`
     * @param float $umbralMaterialidad saldos en o por debajo de este valor se consideran pagados/castigados
     * @param int $edadMaximaDias facturas con esta antigüedad (días desde emisión) o más se castigan, sin importar el saldo
     */
    public function __construct(
        private readonly array $rangos,
        private readonly float $umbralMaterialidad = self::UMBRAL_MATERIALIDAD_DEFECTO,
        private readonly int $edadMaximaDias = self::EDAD_MAXIMA_DIAS_DEFECTO,
    ) {
    }

    /**
     * @param float $saldo saldo propuesto/actual de la factura
     * @param int $diasVencidos días vencidos respecto a la fecha de corte del período (fecha_vencimiento -> fecha_reporte)
     * @param int $edadFacturaDias antigüedad de la factura en días (fecha_emision -> fecha_reporte)
     * @param int $cobroJuridico 0 = ninguno, 1 = jurídico tipo 1, 2 = jurídico tipo 2
     */
    public function calcular(float $saldo, int $diasVencidos, int $edadFacturaDias, int $cobroJuridico): int
    {
        if ($saldo <= $this->umbralMaterialidad || $edadFacturaDias >= $this->edadMaximaDias) {
            return 5;
        }

        if ($cobroJuridico === 1) {
            return 12;
        }

        if ($cobroJuridico === 2) {
            return 13;
        }

        foreach ($this->rangos as $rango) {
            if ($rango->contiene($diasVencidos)) {
                return $rango->codigo;
            }
        }

        throw new \DomainException(sprintf('Ningún rango de novedad cubre %d días vencidos.', $diasVencidos));
    }

    /** True si $codigo fue forzado por el umbral de materialidad/edad — ver PeriodoFacturaService::resolverSaldoYNovedad(). */
    public function esCastigoPorMaterialidadOEdad(float $saldo, int $edadFacturaDias): bool
    {
        return $saldo <= $this->umbralMaterialidad || $edadFacturaDias >= $this->edadMaximaDias;
    }
}
