<?php

namespace App\Entity;

use App\Entity\Gbl\Usuario;
use App\Repository\CasUsuarioRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea cas.cas_usuario — dueño y migrador: este microservicio. Relación
 * usuario <-> CAS; apunta a gbl.usuario (no gbl.empleado) porque un centro
 * de servicio es un establecimiento externo, no necesariamente tiene un
 * empleado asociado.
 *
 * UNIQUE(cas_id, usuario_id): un usuario no puede estar duplicado dentro
 * del mismo CAS (constraint agregada por esta app, no está en el script
 * original — exigida por la regla de negocio).
 */
#[ORM\Entity(repositoryClass: CasUsuarioRepository::class)]
#[ORM\Table(name: 'cas_usuario', schema: 'cas')]
#[ORM\UniqueConstraint(name: 'uq_cas_usuario_cas_id_usuario_id', columns: ['cas_id', 'usuario_id'])]
class CasUsuario
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cas::class)]
    #[ORM\JoinColumn(name: 'cas_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Cas $cas;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Cas $cas, Usuario $usuario)
    {
        $this->cas = $cas;
        $this->usuario = $usuario;
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

    public function getUsuario(): Usuario
    {
        return $this->usuario;
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
