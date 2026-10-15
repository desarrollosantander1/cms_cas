<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ProductoImpuestoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.producto_impuesto — dueño y migrador: este microservicio (tabla
 * creada en Version20260912120100 junto al resto del catálogo de producto).
 * Asocia un gbl.producto (ver Producto) con una tarifa concreta de
 * gbl.impuesto_config (ver ImpuestoConfig) — ej. qué IVA aplica a un item.
 *
 * `producto` e `impuestoConfig` son nullable porque así están las FK reales
 * en BD (sin NOT NULL); `estado` sí es NOT NULL boolean (a diferencia de
 * gbl.impuesto/gbl.impuesto_config, donde es varchar nullable).
 */
#[ORM\Entity(repositoryClass: ProductoImpuestoRepository::class)]
#[ORM\Table(name: 'producto_impuesto', schema: 'gbl')]
class ProductoImpuesto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteId = null;

    #[ORM\ManyToOne(targetEntity: Producto::class)]
    #[ORM\JoinColumn(name: 'producto_id', referencedColumnName: 'id', nullable: true)]
    private ?Producto $producto = null;

    #[ORM\ManyToOne(targetEntity: ImpuestoConfig::class)]
    #[ORM\JoinColumn(name: 'impuesto_config_id', referencedColumnName: 'id', nullable: true)]
    private ?ImpuestoConfig $impuestoConfig = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $creadoEn = null;

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

    public function getProducto(): ?Producto
    {
        return $this->producto;
    }

    public function setProducto(?Producto $producto): static
    {
        $this->producto = $producto;

        return $this;
    }

    public function getImpuestoConfig(): ?ImpuestoConfig
    {
        return $this->impuestoConfig;
    }

    public function setImpuestoConfig(?ImpuestoConfig $impuestoConfig): static
    {
        $this->impuestoConfig = $impuestoConfig;

        return $this;
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

    public function getCreadoEn(): ?\DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function setCreadoEn(?\DateTimeImmutable $creadoEn): static
    {
        $this->creadoEn = $creadoEn;

        return $this;
    }
}
