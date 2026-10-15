<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ClienteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Capa espejo: copia local de un cliente de NetSuite, poblada por
 * IngestaClienteService vía POST /sync/clientes (push, nunca la consultamos
 * en vivo). Sin lógica de negocio propia — es un reflejo, no un dominio.
 *
 * Vive en el esquema `gbl` (no `datacredito`) porque es un dato maestro
 * potencialmente compartido por otros módulos, no exclusivo de este
 * reporte — pero `datacredito` sigue siendo su dueño: este servicio es
 * el único que la escribe y migra. No confundir con `src/Entity/Gbl/*`,
 * que sí son copias de solo lectura de tablas que otro servicio posee
 * (ver Reestructuración de esquema, 2026-08-26, en el plan).
 */
#[ORM\Entity(repositoryClass: ClienteRepository::class)]
#[ORM\Table(name: 'cliente', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_cliente_netsuite_id', columns: ['netsuite_id'])]
class Cliente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255)]
    private string $netsuiteId;

    #[ORM\Column(name: 'numero_identidad', type: 'string', length: 255)]
    private string $numeroIdentidad;

    /** Normalizado en gbl.cliente_tipo_identificacion (antes varchar suelto 'N'/'C') — ver esa entidad. */
    #[ORM\ManyToOne(targetEntity: ClienteTipoIdentificacion::class)]
    #[ORM\JoinColumn(name: 'tipo_identificacion_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ClienteTipoIdentificacion $tipoIdentificacion = null;

    #[ORM\Column(name: 'razon_social', type: 'string', length: 255)]
    private string $razonSocial;

    /** Persona natural — separado de razonSocial (persona jurídica), NetSuite envía uno u otro según tipoIdentificacion. */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $apellido1 = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $apellido2 = null;

    #[ORM\Column(name: 'nombre_comercial', type: 'string', length: 255, nullable: true)]
    private ?string $nombreComercial = null;

    /**
     * Normalizado en gbl.cliente_tipo (antes dos smallint sueltos:
     * tipoCliente sin uso real y tipoTercero, el filtro nacional/extranjero
     * de findElegibles() — tipoCliente absorbe esa responsabilidad, ver
     * docblock de ClienteTipo).
     */
    #[ORM\ManyToOne(targetEntity: ClienteTipo::class)]
    #[ORM\JoinColumn(name: 'tipo_cliente_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ClienteTipo $tipoCliente = null;

    /** Reemplaza el CRM_CLIENTE.MEDIO='b' del legacy — gate de consentimiento para reportar. */
    #[ORM\Column(name: 'autoriza_reporte', type: 'boolean', options: ['default' => false])]
    private bool $autorizaReporte = false;

    /** 0 = ninguno, 1 = jurídico tipo 1, 2 = jurídico tipo 2 — ver CalculadorNovedad. */
    #[ORM\Column(name: 'cobro_juridico', type: 'smallint', nullable: true)]
    private ?int $cobroJuridico = null;

    /** Activo/inactivo en NetSuite (ej. isInactive invertido) — no confundir con autorizaReporte (consentimiento). */
    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'sincronizado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $sincronizadoEn;

    public function __construct(string $netsuiteId, string $numeroIdentidad, string $razonSocial)
    {
        $this->netsuiteId = $netsuiteId;
        $this->numeroIdentidad = $numeroIdentidad;
        $this->razonSocial = $razonSocial;
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

    public function getNumeroIdentidad(): string
    {
        return $this->numeroIdentidad;
    }

    public function setNumeroIdentidad(string $numeroIdentidad): static
    {
        $this->numeroIdentidad = $numeroIdentidad;

        return $this;
    }

    public function getTipoIdentificacion(): ?ClienteTipoIdentificacion
    {
        return $this->tipoIdentificacion;
    }

    public function setTipoIdentificacion(?ClienteTipoIdentificacion $tipoIdentificacion): static
    {
        $this->tipoIdentificacion = $tipoIdentificacion;

        return $this;
    }

    public function getRazonSocial(): string
    {
        return $this->razonSocial;
    }

    public function setRazonSocial(string $razonSocial): static
    {
        $this->razonSocial = $razonSocial;

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

    public function getApellido1(): ?string
    {
        return $this->apellido1;
    }

    public function setApellido1(?string $apellido1): static
    {
        $this->apellido1 = $apellido1;

        return $this;
    }

    public function getApellido2(): ?string
    {
        return $this->apellido2;
    }

    public function setApellido2(?string $apellido2): static
    {
        $this->apellido2 = $apellido2;

        return $this;
    }

    public function getNombreComercial(): ?string
    {
        return $this->nombreComercial;
    }

    public function setNombreComercial(?string $nombreComercial): static
    {
        $this->nombreComercial = $nombreComercial;

        return $this;
    }

    public function getTipoCliente(): ?ClienteTipo
    {
        return $this->tipoCliente;
    }

    public function setTipoCliente(?ClienteTipo $tipoCliente): static
    {
        $this->tipoCliente = $tipoCliente;

        return $this;
    }

    public function isAutorizaReporte(): bool
    {
        return $this->autorizaReporte;
    }

    public function setAutorizaReporte(bool $autorizaReporte): static
    {
        $this->autorizaReporte = $autorizaReporte;

        return $this;
    }

    public function getCobroJuridico(): ?int
    {
        return $this->cobroJuridico;
    }

    public function setCobroJuridico(?int $cobroJuridico): static
    {
        $this->cobroJuridico = $cobroJuridico;

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

    /**
     * Nombre a mostrar en listados/reportes, según el código de
     * tipoIdentificacion (ver ClienteTipoIdentificacion::$codigo):
     * 'N' (NIT) → razónSocial; 'C' (CEDULA) → nombre+apellidos;
     * sin tipoIdentificacion → el primero no vacío entre razónSocial,
     * nombre+apellidos y nombreComercial.
     */
    public function getNombreMostrar(): string
    {
        $nombreCompleto = $this->nombreCompleto();

        return match ($this->tipoIdentificacion?->getCodigo()) {
            'N' => $this->razonSocial,
            'C' => $nombreCompleto,
            default => self::primerNoVacio($this->razonSocial, $nombreCompleto, $this->nombreComercial ?? ''),
        };
    }

    private function nombreCompleto(): string
    {
        $partes = array_filter(
            [$this->nombre, $this->apellido1, $this->apellido2],
            static fn (?string $valor): bool => $valor !== null && trim($valor) !== '',
        );

        return implode(' ', $partes);
    }

    private static function primerNoVacio(string ...$valores): string
    {
        foreach ($valores as $valor) {
            if (trim($valor) !== '') {
                return $valor;
            }
        }

        return '';
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
