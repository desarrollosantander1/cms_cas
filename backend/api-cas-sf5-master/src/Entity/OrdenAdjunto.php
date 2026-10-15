<?php

namespace App\Entity;

use App\Entity\Gbl\AdjuntoTipo;
use App\Repository\OrdenAdjuntoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea cas.orden_adjunto — dueño y migrador: este microservicio. Archivos
 * adjuntos de una orden (ej. foto del comprobante).
 *
 * No guarda su propia ruta COMPLETA de archivo físico: la URL de descarga se
 * arma en tiempo de consulta a partir de `adjuntoTipo` (baseUrl + directorio)
 * + el cas/orden (todos los adjuntos de una misma orden comparten esa
 * carpeta) + `ruta` — ver ArchivoAdjuntoUploader y
 * OrdenController::urlAdjunto(). `nombre` y `ruta` se diferencian
 * (Version20261009133021) porque pueden no coincidir:
 * - `nombre`: el nombre original tal como se subió, solo para mostrárselo al
 *   usuario.
 * - `ruta`: el nombre real con el que el archivo quedó en disco — igual a
 *   `nombre` salvo que ya existiera otro adjunto de la orden con ese mismo
 *   nombre, caso en que el uploader lo desambigua (ej. "foto (1).jpg").
 *
 * `estado` es `integer` (no `boolean`, a diferencia de todas las demás
 * tablas de este esquema) — así quedó definido en el script original
 * (`tablas-logica-cas.sql`) y así se creó la columna; se respeta tal cual
 * para no desalinear la entidad de la base de datos ya migrada.
 */
#[ORM\Entity(repositoryClass: OrdenAdjuntoRepository::class)]
#[ORM\Table(name: 'orden_adjunto', schema: 'cas')]
class OrdenAdjunto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Orden::class)]
    #[ORM\JoinColumn(name: 'orden_id', referencedColumnName: 'id', nullable: false)]
    private Orden $orden;

    #[ORM\Column(type: 'string', length: 510)]
    private string $nombre;

    #[ORM\Column(type: 'string', length: 510)]
    private string $ruta;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $extension = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $peso = null;

    /** Columna agregada por Version20260924203625, nullable: ningún adjunto existente la trae todavía (Fase 3 de órdenes sigue pendiente a nivel de API/frontend). */
    #[ORM\ManyToOne(targetEntity: AdjuntoTipo::class)]
    #[ORM\JoinColumn(name: 'adjunto_tipo_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?AdjuntoTipo $adjuntoTipo = null;

    /** Columna real `jsonb`; Doctrine no distingue json/jsonb en Postgres — se mapea con el tipo abstracto `json`. */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Orden $orden, string $nombre, string $ruta)
    {
        $this->orden = $orden;
        $this->nombre = $nombre;
        $this->ruta = $ruta;
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

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getRuta(): string
    {
        return $this->ruta;
    }

    public function setRuta(string $ruta): static
    {
        $this->ruta = $ruta;

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

    public function getPeso(): ?string
    {
        return $this->peso;
    }

    public function setPeso(?string $peso): static
    {
        $this->peso = $peso;

        return $this;
    }

    public function getAdjuntoTipo(): ?AdjuntoTipo
    {
        return $this->adjuntoTipo;
    }

    public function setAdjuntoTipo(?AdjuntoTipo $adjuntoTipo): static
    {
        $this->adjuntoTipo = $adjuntoTipo;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getEstado(): bool
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
