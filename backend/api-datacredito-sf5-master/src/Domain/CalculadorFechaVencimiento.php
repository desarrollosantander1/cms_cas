<?php

namespace App\Domain;

/**
 * "Fecha de vencimiento" de una factura, portada literalmente del CASE de
 * DataCreditoRepository::obligacionesCierreMes() del legacy — NO es
 * simplemente fecha_emision + plazo_pago_dias. Plazos de facturación de
 * 8/15/45/60 días colapsan todos a un vencimiento de 60 días; el resto usa
 * el plazo literal. Clase de dominio pura: sin Doctrine ni Symfony.
 *
 * Único punto de esta regla en el servicio — usada tanto por
 * PeriodoFacturaService (dias_vencidos, que alimenta CalculadorNovedad) como
 * por GeneradorTxtService (FECHA_VENCIMIENTO/EDAD_INCUMPLIMIENTO/
 * FECHA_LIMITE_PAGO/FECHA_PAGO del plano) — antes de esta clase, cada lado
 * calculaba la fecha de vencimiento con una suma simple, sin este mapeo,
 * lo que daba una fecha (y por tanto una novedad) distinta a la del legacy
 * para cualquier factura con uno de esos 4 plazos.
 *
 * $plazosColapsados/$diasGraciaEstandar antes vivían como constantes de
 * clase; ahora se reciben por constructor (defaults = mismos valores de
 * siempre) para que datacredito.configuracion pueda fijarlos sin romper la
 * pureza de esta clase — ver ConfiguracionService::calculadorFechaVencimiento(),
 * único punto que la arma con los valores vigentes.
 */
final class CalculadorFechaVencimiento
{
    private const PLAZOS_COLAPSADOS_A_GRACIA_ESTANDAR_DEFECTO = [8, 15, 45, 60];

    private const DIAS_GRACIA_ESTANDAR_DEFECTO = 60;

    /** @param int[] $plazosColapsados plazos de facturación que colapsan a $diasGraciaEstandar en vez de su valor literal */
    public function __construct(
        private readonly array $plazosColapsados = self::PLAZOS_COLAPSADOS_A_GRACIA_ESTANDAR_DEFECTO,
        private readonly int $diasGraciaEstandar = self::DIAS_GRACIA_ESTANDAR_DEFECTO,
    ) {
    }

    /**
     * Las ramas 30/90/0/"otro" del CASE original son matemáticamente
     * idénticas a sumar el plazo literal — se preservan explícitas aquí
     * solo por trazabilidad 1:1 con el SQL legacy, no porque hagan algo
     * distinto de sumar $plazoPagoDias.
     */
    public function calcular(\DateTimeImmutable $fechaEmision, int $plazoPagoDias): \DateTimeImmutable
    {
        $dias = in_array($plazoPagoDias, $this->plazosColapsados, true)
            ? $this->diasGraciaEstandar
            : $plazoPagoDias;

        return $fechaEmision->modify(sprintf('+%d days', $dias));
    }
}
