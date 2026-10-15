<?php

namespace App\Entity;

use App\Repository\OrdenCartaCambioRepository;
use Doctrine\ORM\Mapping as ORM;

/** Mapea cas.orden_carta_cambio — dueño y migrador: este microservicio. Carta de cambio asociada a una orden (ver Cas::getCartaCambioVence()). */
#[ORM\Entity(repositoryClass: OrdenCartaCambioRepository::class)]
#[ORM\Table(name: 'orden_carta_cambio', schema: 'cas')]
class OrdenCartaCambio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Orden::class)]
    #[ORM\JoinColumn(name: 'orden_id', referencedColumnName: 'id', nullable: false)]
    private Orden $orden;

    #[ORM\Column(name: 'fecha_vencimiento', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaVencimiento = null;

    #[ORM\Column(name: 'fecha_uso', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $fechaUso = null;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Orden $orden)
    {
        $this->orden = $orden;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrden(): Orden
    {
        return $this->orden;
    }

    public function getFechaVencimiento(): ?\DateTimeImmutable
    {
        return $this->fechaVencimiento;
    }

    public function setFechaVencimiento(?\DateTimeImmutable $fechaVencimiento): static
    {
        $this->fechaVencimiento = $fechaVencimiento;

        return $this;
    }

    public function getFechaUso(): ?\DateTimeImmutable
    {
        return $this->fechaUso;
    }

    public function setFechaUso(?\DateTimeImmutable $fechaUso): static
    {
        $this->fechaUso = $fechaUso;

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
