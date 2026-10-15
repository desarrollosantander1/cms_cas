<?php

namespace App\Domain;

/**
 * Un rango [desde, hasta] de días vencidos asociado a un código de novedad
 * (ej. 30-59 días → novedad 6). Value object inmutable, sin dependencias de
 * Doctrine — se construye a partir de filas del catálogo `novedad` en
 * NovedadCatalogo, pero no sabe nada de cómo llegaron ahí.
 */
final readonly class RangoDiasNovedad
{
    /** @param int|null $hasta null = sin límite superior (ej. "120+ días") */
    public function __construct(
        public int $codigo,
        public int $desde,
        public ?int $hasta,
    ) {
    }

    public function contiene(int $diasVencidos): bool
    {
        return $diasVencidos >= $this->desde && ($this->hasta === null || $diasVencidos <= $this->hasta);
    }
}
