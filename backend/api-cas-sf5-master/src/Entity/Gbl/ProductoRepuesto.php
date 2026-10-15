<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ProductoRepuestoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.producto_repuesto — dueño y migrador: este microservicio (tabla
 * creada en Version20260929150000, ver
 * .scratch/cas/tablas-logica-cas-parte-2.sql). Repuestos de un gbl.producto
 * compuesto: `producto` es el producto padre, `repuesto` es otro
 * gbl.producto que lo compone (auto-referencia, no una tabla distinta) y
 * `empleado` registra quién dio de alta la relación.
 */
#[ORM\Entity(repositoryClass: ProductoRepuestoRepository::class)]
#[ORM\Table(name: 'producto_repuesto', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_producto_repuesto_netsuite_id', columns: ['netsuite_id'])]
class ProductoRepuesto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteId = null;

    #[ORM\ManyToOne(targetEntity: Producto::class)]
    #[ORM\JoinColumn(name: 'producto_id', referencedColumnName: 'id', nullable: false)]
    private Producto $producto;

    /** Otro gbl.producto que compone a `producto` como repuesto — auto-referencia. */
    #[ORM\ManyToOne(targetEntity: Producto::class)]
    #[ORM\JoinColumn(name: 'repuesto_id', referencedColumnName: 'id', nullable: false)]
    private Producto $repuesto;

    #[ORM\ManyToOne(targetEntity: Empleado::class)]
    #[ORM\JoinColumn(name: 'empleado_id', referencedColumnName: 'id', nullable: false)]
    private Empleado $empleado;

    /** numeric(12,2) — como string para no perder precisión (convención estándar de Doctrine para `decimal`). */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => 1])]
    private string $cantidad = '1';

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Producto $producto, Producto $repuesto, Empleado $empleado)
    {
        $this->producto = $producto;
        $this->repuesto = $repuesto;
        $this->empleado = $empleado;
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

    public function getProducto(): Producto
    {
        return $this->producto;
    }

    public function setProducto(Producto $producto): static
    {
        $this->producto = $producto;

        return $this;
    }

    public function getRepuesto(): Producto
    {
        return $this->repuesto;
    }

    public function setRepuesto(Producto $repuesto): static
    {
        $this->repuesto = $repuesto;

        return $this;
    }

    public function getEmpleado(): Empleado
    {
        return $this->empleado;
    }

    public function setEmpleado(Empleado $empleado): static
    {
        $this->empleado = $empleado;

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
