<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ProductoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.producto — dueño y migrador: este microservicio (tabla creada
 * en Version20260912120100 junto al resto del catálogo de producto).
 * Catálogo de productos; la referencian `Orden::$producto` y
 * `OrdenDetalle::$producto` (ManyToOne).
 *
 * `tipoCombo` es opcional (un producto normal no es un combo); `marca`,
 * `sublinea` y `abc` son obligatorias en BD. `netsuiteId` es nullable pero
 * único cuando viene informado (UNIQUE permite múltiples NULL en Postgres).
 */
#[ORM\Entity(repositoryClass: ProductoRepository::class)]
#[ORM\Table(name: 'producto', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_producto_netsuite_id', columns: ['netsuite_id'])]
class Producto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteId = null;

    #[ORM\ManyToOne(targetEntity: ProductoTipoCombo::class)]
    #[ORM\JoinColumn(name: 'tipo_combo_id', referencedColumnName: 'id', nullable: true)]
    private ?ProductoTipoCombo $tipoCombo = null;

    #[ORM\ManyToOne(targetEntity: Marca::class)]
    #[ORM\JoinColumn(name: 'marca_id', referencedColumnName: 'id', nullable: false)]
    private Marca $marca;

    #[ORM\ManyToOne(targetEntity: LineaSublinea::class)]
    #[ORM\JoinColumn(name: 'sublinea_id', referencedColumnName: 'id', nullable: false)]
    private LineaSublinea $sublinea;

    #[ORM\ManyToOne(targetEntity: Abc::class)]
    #[ORM\JoinColumn(name: 'abc_id', referencedColumnName: 'id', nullable: false)]
    private Abc $abc;

    #[ORM\Column(type: 'string', length: 255)]
    private string $item;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'publicado_en', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $publicadoEn = null;

    #[ORM\Column(name: 'sincronizado_en', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $sincronizadoEn = null;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(string $item, Marca $marca, LineaSublinea $sublinea, Abc $abc)
    {
        $this->item = $item;
        $this->marca = $marca;
        $this->sublinea = $sublinea;
        $this->abc = $abc;
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

    public function getTipoCombo(): ?ProductoTipoCombo
    {
        return $this->tipoCombo;
    }

    public function setTipoCombo(?ProductoTipoCombo $tipoCombo): static
    {
        $this->tipoCombo = $tipoCombo;

        return $this;
    }

    public function getMarca(): Marca
    {
        return $this->marca;
    }

    public function setMarca(Marca $marca): static
    {
        $this->marca = $marca;

        return $this;
    }

    public function getSublinea(): LineaSublinea
    {
        return $this->sublinea;
    }

    public function setSublinea(LineaSublinea $sublinea): static
    {
        $this->sublinea = $sublinea;

        return $this;
    }

    public function getAbc(): Abc
    {
        return $this->abc;
    }

    public function setAbc(Abc $abc): static
    {
        $this->abc = $abc;

        return $this;
    }

    public function getItem(): string
    {
        return $this->item;
    }

    public function setItem(string $item): static
    {
        $this->item = $item;

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

    public function getPublicadoEn(): ?\DateTimeImmutable
    {
        return $this->publicadoEn;
    }

    public function setPublicadoEn(?\DateTimeImmutable $publicadoEn): static
    {
        $this->publicadoEn = $publicadoEn;

        return $this;
    }

    public function getSincronizadoEn(): ?\DateTimeImmutable
    {
        return $this->sincronizadoEn;
    }

    public function setSincronizadoEn(?\DateTimeImmutable $sincronizadoEn): static
    {
        $this->sincronizadoEn = $sincronizadoEn;

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
