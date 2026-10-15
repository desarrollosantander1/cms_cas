<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ProductoComboRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.producto_combo — dueño y migrador: este microservicio (tabla
 * creada en Version20260912120100 junto al resto del catálogo de producto).
 * Marca un gbl.producto (ver Producto) como cabecera de combo; el detalle
 * de qué productos componen ese combo vive en gbl.producto_combo_detalle
 * (ver ProductoComboDetalle).
 *
 * `producto` es nullable porque así está la FK real en BD (sin NOT NULL).
 */
#[ORM\Entity(repositoryClass: ProductoComboRepository::class)]
#[ORM\Table(name: 'producto_combo', schema: 'gbl')]
class ProductoCombo
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
