<?php

namespace App\Service;

/**
 * Cómo se determina la novedad al guardar una factura en
 * PeriodoFacturaService::guardarNovedades() — ver Fase 3 del plan para la
 * justificación de por qué esto es un parámetro y no una jerarquía Strategy.
 */
enum ModoNovedad
{
    /** Flujo normal: la novedad SIEMPRE se recalcula server-side; lo que mande el cliente se ignora. */
    case AUTOMATICA;

    /** Flujo reclasificados: la novedad viene del request, validada 1-13. */
    case MANUAL;
}
