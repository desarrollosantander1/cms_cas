<?php

namespace App\Domain;

/**
 * Interés de mora de una factura, portado literalmente del legacy
 * (GeneradorCartasService::generarCartaCliente):
 *
 *   INTERES = SALDO · (TASA_MENSUAL / 100) · díasMora / 30
 *
 * La tasa mensual es configurable en datacredito.tasa_interes
 * (ver TasaInteresService / TasaInteres) — el llamador la resuelve y
 * la pasa como argumento. TASA_MENSUAL_DEFECTO (2%) solo aplica cuando esa
 * tabla está vacía.
 *
 * Clase de dominio pura: sin Doctrine ni Symfony, igual que
 * CalculadorFechaVencimiento / CalculadorNovedad.
 */
final class CalculadorInteres
{
    /** Tasa de mora mensual usada si no hay ninguna configurada (2.0 = 2% mensual). */
    public const TASA_MENSUAL_DEFECTO = 2.0;

    private const DIAS_MES = 30;

    /**
     * @param float $saldo        saldo pendiente de la factura
     * @param int   $diasMora     días en mora (>= 0; el llamador ya sumó paramsDays si aplica)
     * @param float $tasaMensual  tasa de mora mensual en porcentaje (ej. 2.0)
     */
    public function calcular(float $saldo, int $diasMora, float $tasaMensual): float
    {
        if ($saldo <= 0 || $diasMora <= 0 || $tasaMensual <= 0) {
            return 0.0;
        }

        return $saldo * ($tasaMensual / 100) * $diasMora / self::DIAS_MES;
    }
}
