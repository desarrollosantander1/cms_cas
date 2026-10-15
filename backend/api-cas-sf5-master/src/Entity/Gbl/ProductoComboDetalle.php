<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ProductoComboDetalleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.producto_combo_detalle — dueño y migrador: este microservicio
 * (tabla creada en Version20260912120100 junto al resto del catálogo de
 * producto). Detalle de qué gbl.producto (ver Producto) componen un combo
 * (ver ProductoCombo) — filas hijas de `combo`, cada una apuntando además al
 * producto componente.
 *
 * `combo` y `producto` son nullable porque así están las FK reales en BD
 * (sin NOT NULL).
 */
#[ORM\Entity(repositoryClass: ProductoComboDetalleRepository::class)]
#[ORM\Table(name: 'producto_combo_detalle', schema: 'gbl')]
class ProductoComboDetalle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteId = null;

    #[ORM\ManyToOne(targetEntity: ProductoCombo::class)]
    #[ORM\JoinColumn(name: 'combo_id', referencedColumnName: 'id', nullable: true)]
    private ?ProductoCombo $combo = null;

    #[ORM\ManyToOne(targetEntity: Producto::class)]
    #[ORM\JoinColumn(name: 'producto_id', referencedColumnName: 'id', nullable: true)]
    private ?Producto $producto = null;

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

    public function getCombo(): ?ProductoCombo
    {
        return $this->combo;
    }

    public function setCombo(?ProductoCombo $combo): static
    {
        $this->combo = $combo;

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
