<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\LineaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.linea — dueño y migrador: este microservicio (tabla creada en
 * Version20260912120100 junto al resto del catálogo de producto). Catálogo
 * de líneas de producto; cada línea se subdivide en gbl.linea_sublinea (ver
 * LineaSublinea), que es lo que realmente referencia gbl.producto.
 *
 * `netsuiteId` es nullable porque el catálogo admite filas locales que no
 * vienen del espejo de NetSuite.
 */
#[ORM\Entity(repositoryClass: LineaRepository::class)]
#[ORM\Table(name: 'linea', schema: 'gbl')]
class Linea
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $descripcion;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(string $descripcion)
    {
        $this->descripcion = $descripcion;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNetsuiteId(): ?string
    {
        return $this->netsuiteId;
    }

    public function setNetsuiteId(?string $netsuiteId): static
    {
        $this->netsuiteId = $netsuiteId;

        return $this;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
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
}
