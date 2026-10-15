<?php

namespace App\Entity;

use App\Repository\NovedadRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Catálogo de códigos "novedad" — antes solo existían como enteros mágicos
 * en el SQL/PHP del legacy. `codigo` es la clave de negocio (1,5,6,7,8,9,12,13),
 * `id` es la PK surrogate a la que apuntan las FK de otras tablas (ver
 * ReporteFactura::$novedad) — así una FK nunca depende de un valor que,
 * aunque estable en la práctica, es conceptualmente un dato, no una llave.
 * Sembrado una sola vez en la migración; sin timestamps, no se edita en runtime.
 */
#[ORM\Entity(repositoryClass: NovedadRepository::class)]
#[ORM\Table(name: 'novedad', schema: 'datacredito')]
#[ORM\UniqueConstraint(name: 'uq_novedad_codigo', columns: ['codigo'])]
class Novedad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'smallint')]
    private int $codigo;

    #[ORM\Column(type: 'string', length: 255)]
    private string $descripcion;

    /** Límite inferior del rango de días vencidos (antes rango_dias_desde); nullable para códigos manuales (5,12,13). */
    #[ORM\Column(name: 'rango_dias', type: 'smallint', nullable: true)]
    private ?int $rangoDias = null;

    /** Límite superior del mismo rango; null = sin límite superior (ej. "120+ días"). */
    #[ORM\Column(name: 'rango_dias_hasta', type: 'smallint', nullable: true)]
    private ?int $rangoDiasHasta = null;

    /** true para 5 (pagada/castigada), 12 y 13 (cobro jurídico) — no se derivan de un rango de días. */
    #[ORM\Column(name: 'es_manual', type: 'boolean', options: ['default' => false])]
    private bool $esManual = false;

    public function __construct(int $codigo, string $descripcion, bool $esManual, ?int $rangoDias = null, ?int $rangoDiasHasta = null)
    {
        $this->codigo = $codigo;
        $this->descripcion = $descripcion;
        $this->esManual = $esManual;
        $this->rangoDias = $rangoDias;
        $this->rangoDiasHasta = $rangoDiasHasta;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): int
    {
        return $this->codigo;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function getRangoDias(): ?int
    {
        return $this->rangoDias;
    }

    public function getRangoDiasHasta(): ?int
    {
        return $this->rangoDiasHasta;
    }

    public function isEsManual(): bool
    {
        return $this->esManual;
    }
}
