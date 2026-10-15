<?php

namespace App\Entity;

use App\Entity\Gbl\ClienteSucursal;
use App\Entity\Gbl\PaisDptoCiudad;
use App\Repository\CasRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea cas.cas — dueño y migrador: este microservicio.
 *
 * `clienteSucursal` es opcional (un CAS puede existir sin cliente/sucursal
 * asociado) y único (UNIQUE(cliente_sucursal_id) en BD: una sucursal solo
 * puede estar asociada a un CAS). `ciudad` solo aplica sin sucursal (con
 * sucursal, la ubicación la define el cliente/sucursal). `nit` también solo
 * aplica sin sucursal. `nombre`/`direccion`/`telefono`, en cambio, son
 * editables con o sin sucursal — el CAS puede registrar sus propios datos
 * aunque tenga sucursal asociada; si quedan vacíos, el listado hace
 * COALESCE(cas.campo, sucursal.campo) en vez de mostrar el propio (ver
 * CasRepository::listar() y los métodos *Mostrar() más abajo).
 */
#[ORM\Entity(repositoryClass: CasRepository::class)]
#[ORM\Table(name: 'cas', schema: 'cas')]
#[ORM\UniqueConstraint(name: 'uq_cas_cliente_sucursal_id', columns: ['cliente_sucursal_id'])]
#[ORM\UniqueConstraint(name: 'uq_cas_nit', columns: ['nit'])]
class Cas
{
    /** Ver Version20261009203340 — mismo valor con el que quedó el DEFAULT de `mime_permitidos` en BD. */
    private const MIME_PERMITIDOS_DEFECTO = 'application/pdf, image/jpeg, image/png, image/webp, image/gif, image/heic, image/heif, video/mp4, video/quicktime, video/webm, video/x-msvideo, video/3gpp';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PaisDptoCiudad::class)]
    #[ORM\JoinColumn(name: 'ciudad_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PaisDptoCiudad $ciudad = null;

    #[ORM\ManyToOne(targetEntity: ClienteSucursal::class)]
    #[ORM\JoinColumn(name: 'cliente_sucursal_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ClienteSucursal $clienteSucursal = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $nit = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $direccion = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $telefono = null;

    /** Días en los que vence una carta de cambio, personalizable por CAS. Por defecto 365. */
    #[ORM\Column(name: 'carta_cambio_vence', type: 'integer', options: ['default' => 365])]
    private int $cartaCambioVence = 365;

    /**
     * Tipos de archivo (MIME) permitidos para los adjuntos de las órdenes de reparación de este
     * CAS, separados por coma — reemplaza la constante fija que tenía
     * ArchivoAdjuntoUploader::MIME_PERMITIDOS (ver Version20261009203340 y OrdenService).
     */
    #[ORM\Column(name: 'mime_permitidos', type: 'string', length: 255, options: ['default' => self::MIME_PERMITIDOS_DEFECTO])]
    private string $mimePermitidos = self::MIME_PERMITIDOS_DEFECTO;

    /**
     * numeric(12,2) — como string para no perder precisión (convención estándar de Doctrine para
     * `decimal`). Tamaño máximo en MB permitido por cada archivo adjunto de una orden de
     * reparación — reemplaza ArchivoAdjuntoUploader::TAMANO_MAXIMO_BYTES.
     */
    #[ORM\Column(name: 'maximo_mb_archivo', type: 'decimal', precision: 12, scale: 2, options: ['default' => 50])]
    private string $maximoMbArchivo = '50';

    /**
     * numeric(12,2) — ídem `maximoMbArchivo`. Tamaño máximo en MB permitido para la SUMA de
     * todos los archivos adjuntos de una misma orden (no existía antes: solo se validaba cada
     * archivo por separado — ver OrdenService).
     */
    #[ORM\Column(name: 'maximo_mb_orden', type: 'decimal', precision: 12, scale: 2, options: ['default' => 150])]
    private string $maximoMbOrden = '150';

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCiudad(): ?PaisDptoCiudad
    {
        return $this->ciudad;
    }

    public function setCiudad(?PaisDptoCiudad $ciudad): static
    {
        $this->ciudad = $ciudad;

        return $this;
    }

    public function getClienteSucursal(): ?ClienteSucursal
    {
        return $this->clienteSucursal;
    }

    public function setClienteSucursal(?ClienteSucursal $clienteSucursal): static
    {
        $this->clienteSucursal = $clienteSucursal;

        return $this;
    }

    public function getNit(): ?string
    {
        return $this->nit;
    }

    public function setNit(?string $nit): static
    {
        $this->nit = $nit;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getDireccion(): ?string
    {
        return $this->direccion;
    }

    public function setDireccion(?string $direccion): static
    {
        $this->direccion = $direccion;

        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): static
    {
        $this->telefono = $telefono;

        return $this;
    }

    public function getCartaCambioVence(): int
    {
        return $this->cartaCambioVence;
    }

    public function setCartaCambioVence(int $cartaCambioVence): static
    {
        $this->cartaCambioVence = $cartaCambioVence;

        return $this;
    }

    public function getMimePermitidos(): string
    {
        return $this->mimePermitidos;
    }

    /** @return string[] `mimePermitidos` recortado por coma y sin entradas vacías — lista lista para usar en un `in_array()`. */
    public function getMimePermitidosLista(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->mimePermitidos))));
    }

    public function setMimePermitidos(string $mimePermitidos): static
    {
        $this->mimePermitidos = $mimePermitidos;

        return $this;
    }

    public function getMaximoMbArchivo(): string
    {
        return $this->maximoMbArchivo;
    }

    public function setMaximoMbArchivo(string $maximoMbArchivo): static
    {
        $this->maximoMbArchivo = $maximoMbArchivo;

        return $this;
    }

    public function getMaximoMbOrden(): string
    {
        return $this->maximoMbOrden;
    }

    public function setMaximoMbOrden(string $maximoMbOrden): static
    {
        $this->maximoMbOrden = $maximoMbOrden;

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

    /** COALESCE(cas.nit, cliente.numero_identidad) — ver Fase 2 del prompt original. */
    public function nitMostrar(): ?string
    {
        return $this->nit ?? $this->clienteSucursal?->getCliente()->getNumeroIdentidad();
    }

    /** COALESCE(cas.nombre, cliente_sucursal.nombre). */
    public function nombreMostrar(): ?string
    {
        return $this->nombre ?? $this->clienteSucursal?->getNombre();
    }

    /** COALESCE(cas.direccion, cliente_sucursal.direccion). */
    public function direccionMostrar(): ?string
    {
        return $this->direccion ?? $this->clienteSucursal?->getDireccion();
    }

    /** COALESCE(cas.telefono, cliente_sucursal.telefono). */
    public function telefonoMostrar(): ?string
    {
        return $this->telefono ?? $this->clienteSucursal?->getTelefono();
    }
}
