<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ClienteSucursalRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.cliente_sucursal — propiedad de API datacredito sf4-1 (única que
 * la escribe/migra, poblada desde NetSuite). Este microservicio (CAS) solo
 * la lee (ver dbal.schema_filter): un CAS puede asociarse opcionalmente a
 * una sucursal (UNIQUE por sucursal — una sucursal solo puede tener un CAS),
 * heredando de ahí nombre/dirección/teléfono cuando el CAS no los define
 * explícitamente. Ciudad/departamento están normalizados vía
 * `paisDptoCiudad` (jerarquía pais -> pais_dpto -> pais_dpto_ciudad).
 */
#[ORM\Entity(repositoryClass: ClienteSucursalRepository::class)]
#[ORM\Table(name: 'cliente_sucursal', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_cliente_sucursal_netsuite_id', columns: ['netsuite_id'])]
class ClienteSucursal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255)]
    private string $netsuiteId;

    #[ORM\ManyToOne(targetEntity: Cliente::class)]
    #[ORM\JoinColumn(name: 'cliente_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Cliente $cliente;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $direccion = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(type: 'string', length: 30, nullable: true)]
    private ?string $celular = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $correo = null;

    #[ORM\ManyToOne(targetEntity: PaisDptoCiudad::class)]
    #[ORM\JoinColumn(name: 'pais_ciudad_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?PaisDptoCiudad $paisDptoCiudad = null;

    /** Activo/inactivo en NetSuite (ej. isInactive invertido). */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'sincronizado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $sincronizadoEn;

    public function __construct(string $netsuiteId, Cliente $cliente)
    {
        $this->netsuiteId = $netsuiteId;
        $this->cliente = $cliente;
        $this->sincronizadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNetsuiteId(): string
    {
        return $this->netsuiteId;
    }

    public function getCliente(): Cliente
    {
        return $this->cliente;
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

    public function getCelular(): ?string
    {
        return $this->celular;
    }

    public function setCelular(?string $celular): static
    {
        $this->celular = $celular;

        return $this;
    }

    public function getCorreo(): ?string
    {
        return $this->correo;
    }

    public function setCorreo(?string $correo): static
    {
        $this->correo = $correo;

        return $this;
    }

    public function getPaisDptoCiudad(): ?PaisDptoCiudad
    {
        return $this->paisDptoCiudad;
    }

    public function setPaisDptoCiudad(?PaisDptoCiudad $paisDptoCiudad): static
    {
        $this->paisDptoCiudad = $paisDptoCiudad;

        return $this;
    }

    /** Transitivo vía paisDptoCiudad — ver docblock de la clase. */
    public function getCiudadNombre(): ?string
    {
        return $this->paisDptoCiudad?->getNombre();
    }

    public function getCiudadCodigoDane(): ?string
    {
        return $this->paisDptoCiudad?->getCodigoDane();
    }

    public function getDepartamentoNombre(): ?string
    {
        return $this->paisDptoCiudad?->getPaisDpto()->getNombre();
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

    public function getSincronizadoEn(): \DateTimeImmutable
    {
        return $this->sincronizadoEn;
    }

    public function tocarSincronizado(): static
    {
        $this->sincronizadoEn = new \DateTimeImmutable();

        return $this;
    }
}
