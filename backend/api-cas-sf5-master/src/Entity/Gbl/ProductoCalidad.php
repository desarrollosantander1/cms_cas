<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ProductoCalidadRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.producto_calidad — dueño y migrador: este microservicio (tabla
 * creada en Version20260929150000, ver
 * .scratch/cas/tablas-logica-cas-parte-2.sql). Valor concreto de un campo de
 * ficha técnica/calidad (`calidadTipo`, ver ProductoCalidadTipo) para un
 * gbl.producto — ej. "VOLTAJE = 110" con `unidadMedida` = "V".
 *
 * `unidadMedida` es opcional (no todo campo de calidad tiene unidad, ej.
 * GARANTIA); `producto` y `calidadTipo` son obligatorios en BD.
 */
#[ORM\Entity(repositoryClass: ProductoCalidadRepository::class)]
#[ORM\Table(name: 'producto_calidad', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_producto_calidad_netsuite_id', columns: ['netsuite_id'])]
class ProductoCalidad
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

    #[ORM\ManyToOne(targetEntity: UnidadMedida::class)]
    #[ORM\JoinColumn(name: 'unidad_medida_id', referencedColumnName: 'id', nullable: true)]
    private ?UnidadMedida $unidadMedida = null;

    #[ORM\ManyToOne(targetEntity: ProductoCalidadTipo::class)]
    #[ORM\JoinColumn(name: 'calidad_tipo_id', referencedColumnName: 'id', nullable: false)]
    private ProductoCalidadTipo $calidadTipo;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $valor = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Producto $producto, ProductoCalidadTipo $calidadTipo)
    {
        $this->producto = $producto;
        $this->calidadTipo = $calidadTipo;
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

    public function getUnidadMedida(): ?UnidadMedida
    {
        return $this->unidadMedida;
    }

    public function setUnidadMedida(?UnidadMedida $unidadMedida): static
    {
        $this->unidadMedida = $unidadMedida;

        return $this;
    }

    public function getCalidadTipo(): ProductoCalidadTipo
    {
        return $this->calidadTipo;
    }

    public function setCalidadTipo(ProductoCalidadTipo $calidadTipo): static
    {
        $this->calidadTipo = $calidadTipo;

        return $this;
    }

    public function getValor(): ?string
    {
        return $this->valor;
    }

    public function setValor(?string $valor): static
    {
        $this->valor = $valor;

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
