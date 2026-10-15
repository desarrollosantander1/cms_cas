<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ProductoAdjuntoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.producto_adjunto — dueño y migrador: este microservicio (tabla
 * creada en Version20260929150000, ver
 * .scratch/cas/tablas-logica-cas-parte-2.sql). Asocia un gbl.producto (ver
 * Producto) con un archivo cuyo tipo/ruta base define gbl.adjunto_tipo (ver
 * AdjuntoTipo) — ej. la ficha técnica o foto de un producto.
 *
 * `ruta`/`nombre`/`extension` (agregadas en Version20260929160000) guardan
 * la ubicación real del archivo concreto, igual que en `OrdenAdjunto`;
 * `ruta` y `extension` son nullable en BD, `nombre` es obligatorio.
 */
#[ORM\Entity(repositoryClass: ProductoAdjuntoRepository::class)]
#[ORM\Table(name: 'producto_adjunto', schema: 'gbl')]
class ProductoAdjunto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Producto::class)]
    #[ORM\JoinColumn(name: 'producto_id', referencedColumnName: 'id', nullable: false)]
    private Producto $producto;

    #[ORM\ManyToOne(targetEntity: AdjuntoTipo::class)]
    #[ORM\JoinColumn(name: 'adjunto_tipo_id', referencedColumnName: 'id', nullable: false)]
    private AdjuntoTipo $adjuntoTipo;

    #[ORM\Column(type: 'string', length: 510, nullable: true)]
    private ?string $ruta = null;

    #[ORM\Column(type: 'string', length: 510)]
    private string $nombre;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $extension = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Producto $producto, AdjuntoTipo $adjuntoTipo, string $nombre)
    {
        $this->producto = $producto;
        $this->adjuntoTipo = $adjuntoTipo;
        $this->nombre = $nombre;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAdjuntoTipo(): AdjuntoTipo
    {
        return $this->adjuntoTipo;
    }

    public function setAdjuntoTipo(AdjuntoTipo $adjuntoTipo): static
    {
        $this->adjuntoTipo = $adjuntoTipo;

        return $this;
    }

    public function getRuta(): ?string
    {
        return $this->ruta;
    }

    public function setRuta(?string $ruta): static
    {
        $this->ruta = $ruta;

        return $this;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getExtension(): ?string
    {
        return $this->extension;
    }

    public function setExtension(?string $extension): static
    {
        $this->extension = $extension;

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
