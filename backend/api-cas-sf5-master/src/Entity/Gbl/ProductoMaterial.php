<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ProductoMaterialRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.producto_material — dueño y migrador: este microservicio (tabla
 * creada en Version20260929150000, ver
 * .scratch/cas/tablas-logica-cas-parte-2.sql). Composición de materiales de
 * un gbl.producto (ver Producto y Material); un producto puede tener varios
 * materiales, cada uno con su `porcentaje` de composición.
 */
#[ORM\Entity(repositoryClass: ProductoMaterialRepository::class)]
#[ORM\Table(name: 'producto_material', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_producto_material_netsuite_id', columns: ['netsuite_id'])]
class ProductoMaterial
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

    #[ORM\ManyToOne(targetEntity: Material::class)]
    #[ORM\JoinColumn(name: 'material_id', referencedColumnName: 'id', nullable: false)]
    private Material $material;

    /** numeric(12,2) — como string para no perder precisión (convención estándar de Doctrine para `decimal`). */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $porcentaje = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Producto $producto, Material $material)
    {
        $this->producto = $producto;
        $this->material = $material;
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

    public function getMaterial(): Material
    {
        return $this->material;
    }

    public function setMaterial(Material $material): static
    {
        $this->material = $material;

        return $this;
    }

    public function getPorcentaje(): ?string
    {
        return $this->porcentaje;
    }

    public function setPorcentaje(?string $porcentaje): static
    {
        $this->porcentaje = $porcentaje;

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
