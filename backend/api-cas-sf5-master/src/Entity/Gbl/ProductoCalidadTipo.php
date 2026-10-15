<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ProductoCalidadTipoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.producto_calidad_tipo — dueño y migrador: este microservicio
 * (tabla creada en Version20260929150000 como `calidad_producto_tipo` y
 * renombrada en Version20260929160000 para alinearla con el resto de la
 * familia `producto_*`, ver .scratch/cas/tablas-logica-cas-parte-2.sql).
 * Catálogo de los tipos de campo de ficha técnica/calidad que puede tener un
 * producto (ej. VOLTAJE, FRECUENCIA, POTENCIA, GARANTIA); lo referencia
 * gbl.producto_calidad (ver ProductoCalidad) para saber qué campo es cada
 * valor.
 *
 * `material` queda mapeada tal cual está en el script original: columna
 * entera suelta sin FK ni comentario que explique su uso — no se le puede
 * asignar significado de negocio todavía, así que no se modela como
 * relación a `Material`.
 */
#[ORM\Entity(repositoryClass: ProductoCalidadTipoRepository::class)]
#[ORM\Table(name: 'producto_calidad_tipo', schema: 'gbl')]
class ProductoCalidadTipo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    /** Columna suelta sin FK en el script original — ver docblock de la clase. */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $material = null;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function isEstado(): bool
    {
        return $this->estado;
    }

    public function setEstado(bool $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function getMaterial(): ?int
    {
        return $this->material;
    }

    public function setMaterial(?int $material): static
    {
        $this->material = $material;

        return $this;
    }
}
