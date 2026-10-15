<?php

namespace App\Entity;

use App\Repository\OrdenEstadoRepository;
use Doctrine\ORM\Mapping as ORM;

/** Mapea cas.orden_estado — dueño y migrador: este microservicio. Catálogo de estados de una orden (ej. "Pendiente", "Aprobada", "Rechazada"). */
#[ORM\Entity(repositoryClass: OrdenEstadoRepository::class)]
#[ORM\Table(name: 'orden_estado', schema: 'cas')]
class OrdenEstado
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
}
