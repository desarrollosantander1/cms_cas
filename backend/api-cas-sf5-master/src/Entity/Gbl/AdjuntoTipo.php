<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\AdjuntoTipoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.adjunto_tipo — dueño y migrador: este microservicio (ver
 * Version20260924203625). Catálogo de tipos de adjunto de una orden (ej.
 * "FOTO COMPROBANTE", "FACTURA"), con `baseUrl`/`pathAdjunto`/`directorio`
 * para armar en tiempo de consulta la ruta de almacenamiento/descarga de
 * cada archivo según su tipo — ningún adjunto individual guarda su propia
 * ruta (ver OrdenAdjunto y Version20261008202104).
 *
 * Vive en `gbl` (no `cas`) para poder reutilizarse desde otros
 * microservicios más adelante, igual que gbl.cliente_tipo y el resto del
 * catálogo de producto (ver Entity/Gbl/Producto.php).
 */
#[ORM\Entity(repositoryClass: AdjuntoTipoRepository::class)]
#[ORM\Table(name: 'adjunto_tipo', schema: 'gbl')]
class AdjuntoTipo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $descripcion;

    #[ORM\Column(name: 'base_url', type: 'string', length: 255)]
    private string $baseUrl;

    #[ORM\Column(name: 'path_adjunto', type: 'string', length: 255)]
    private string $pathAdjunto;

    /**
     * Nombre fijo de la subcarpeta de este tipo (ej. "orden-adjuntos"),
     * agregada por Version20261008202104. Nullable igual que `baseUrl`/
     * `pathAdjunto` en su momento: se completa a mano por fila, no todo tipo
     * la tiene configurada todavía.
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $directorio = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(string $descripcion, string $baseUrl, string $pathAdjunto)
    {
        $this->descripcion = $descripcion;
        $this->baseUrl = $baseUrl;
        $this->pathAdjunto = $pathAdjunto;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function setBaseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    public function getPathAdjunto(): string
    {
        return $this->pathAdjunto;
    }

    public function setPathAdjunto(string $pathAdjunto): static
    {
        $this->pathAdjunto = $pathAdjunto;

        return $this;
    }

    public function getDirectorio(): ?string
    {
        return $this->directorio;
    }

    public function setDirectorio(?string $directorio): static
    {
        $this->directorio = $directorio;

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
