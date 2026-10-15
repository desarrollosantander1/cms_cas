<?php

namespace App\Entity;

use App\Repository\CasClienteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea cas.cas_cliente — dueño y migrador: este microservicio. Es el
 * cliente final que compra en un CAS (persona natural identificada por
 * documento), no el `Gbl\Cliente` de NetSuite (que es la empresa/sucursal a
 * la que el CAS mismo puede estar asociado) — dos conceptos distintos que
 * comparten el nombre "cliente" por contexto.
 *
 * `numeroIdentidad` es único por CAS, no globalmente (corregido en
 * Version20260929170000): la misma persona puede ser cliente de más de un
 * centro de servicio, cada uno con su propia fila — ver CasClienteService.
 */
#[ORM\Entity(repositoryClass: CasClienteRepository::class)]
#[ORM\Table(name: 'cas_cliente', schema: 'cas')]
#[ORM\UniqueConstraint(name: 'uq_cas_cliente_cas_numero_identidad', columns: ['cas_id', 'numero_identidad'])]
class CasCliente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cas::class)]
    #[ORM\JoinColumn(name: 'cas_id', referencedColumnName: 'id', nullable: false)]
    private Cas $cas;

    #[ORM\Column(name: 'numero_identidad', type: 'string', length: 255)]
    private string $numeroIdentidad;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nombre;

    #[ORM\Column(type: 'string', length: 255)]
    private string $apellido;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $direccion = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $telefono;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $correo = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Cas $cas, string $numeroIdentidad, string $nombre, string $apellido, string $telefono)
    {
        $this->cas = $cas;
        $this->numeroIdentidad = $numeroIdentidad;
        $this->nombre = $nombre;
        $this->apellido = $apellido;
        $this->telefono = $telefono;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCas(): Cas
    {
        return $this->cas;
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

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getApellido(): string
    {
        return $this->apellido;
    }

    public function setApellido(string $apellido): static
    {
        $this->apellido = $apellido;

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

    public function getTelefono(): string
    {
        return $this->telefono;
    }

    public function setTelefono(string $telefono): static
    {
        $this->telefono = $telefono;

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
