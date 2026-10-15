<?php

namespace App\Entity;

use App\Repository\EmpleadoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * gbl.empleado — un empleado por usuario (relación 1:1). Nace junto con el
 * usuario (ver UsuarioService::crear) y guarda el vínculo hacia el registro
 * de empleado de NetSuite (`netsuiteEmpleadoId`, poblado por el sync).
 *
 * Este servicio es dueño de su CRUD y su migración. Las relaciones de otros
 * módulos que antes apuntaban a gbl.usuario (excepto permisos, log y
 * recuperación de contraseña) ahora referencian gbl.empleado.
 */
#[ORM\Entity(repositoryClass: EmpleadoRepository::class)]
#[ORM\Table(name: 'empleado', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_empleado_usuario', columns: ['usuario_id'])]
#[ORM\UniqueConstraint(name: 'uq_empleado_netsuite_empleado_id', columns: ['netsuite_empleado_id'])]
class Empleado
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\Column(name: 'netsuite_empleado_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteEmpleadoId = null;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Usuario $usuario)
    {
        $this->usuario = $usuario;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): Usuario
    {
        return $this->usuario;
    }

    public function getNetsuiteEmpleadoId(): ?string
    {
        return $this->netsuiteEmpleadoId;
    }

    public function setNetsuiteEmpleadoId(?string $netsuiteEmpleadoId): static
    {
        $this->netsuiteEmpleadoId = $netsuiteEmpleadoId;

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
