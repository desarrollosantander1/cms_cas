<?php

namespace App\Entity;

use App\Entity\Gbl\Producto;
use App\Repository\OrdenDetalleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea cas.orden_detalle — dueño y migrador: este microservicio. Líneas de
 * detalle de una orden (ej. repuestos usados).
 */
#[ORM\Entity(repositoryClass: OrdenDetalleRepository::class)]
#[ORM\Table(name: 'orden_detalle', schema: 'cas')]
class OrdenDetalle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Orden::class)]
    #[ORM\JoinColumn(name: 'orden_id', referencedColumnName: 'id', nullable: false)]
    private Orden $orden;

    #[ORM\ManyToOne(targetEntity: Producto::class)]
    #[ORM\JoinColumn(name: 'producto_id', referencedColumnName: 'id', nullable: true)]
    private ?Producto $producto = null;

    #[ORM\ManyToOne(targetEntity: OrdenDetalleTipo::class)]
    #[ORM\JoinColumn(name: 'detalle_tipo_id', referencedColumnName: 'id', nullable: true)]
    private ?OrdenDetalleTipo $detalleTipo = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $observacion = null;

    /** numeric(12,2) — como string para no perder precisión (convención estándar de Doctrine para `decimal`). */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $cantidad;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Orden $orden, string $cantidad)
    {
        $this->orden = $orden;
        $this->cantidad = $cantidad;
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

    public function getProducto(): ?Producto
    {
        return $this->producto;
    }

    public function setProducto(?Producto $producto): static
    {
        $this->producto = $producto;

        return $this;
    }

    public function getDetalleTipo(): ?OrdenDetalleTipo
    {
        return $this->detalleTipo;
    }

    public function setDetalleTipo(?OrdenDetalleTipo $detalleTipo): static
    {
        $this->detalleTipo = $detalleTipo;

        return $this;
    }

    public function getObservacion(): ?string
    {
        return $this->observacion;
    }

    public function setObservacion(?string $observacion): static
    {
        $this->observacion = $observacion;

        return $this;
    }

    public function getCantidad(): string
    {
        return $this->cantidad;
    }

    public function setCantidad(string $cantidad): static
    {
        $this->cantidad = $cantidad;

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
