<?php

namespace App\Entity;

use App\Repository\OrdenDetalleTipoRepository;
use Doctrine\ORM\Mapping as ORM;

/** Mapea cas.orden_detalle_tipo — dueño y migrador: este microservicio. Valores como REPUESTO, REPUESTO ADICIONAL. */
#[ORM\Entity(repositoryClass: OrdenDetalleTipoRepository::class)]
#[ORM\Table(name: 'orden_detalle_tipo', schema: 'cas')]
class OrdenDetalleTipo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

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
