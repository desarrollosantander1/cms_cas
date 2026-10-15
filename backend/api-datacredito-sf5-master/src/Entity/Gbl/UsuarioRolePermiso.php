<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\UsuarioRolePermisoRepository;
use Doctrine\ORM\Mapping as ORM;

/** Mapea gbl.usuario_role_permiso — propiedad de API users sf4-1, solo lectura aquí (ver dbal.schema_filter). */
#[ORM\Entity(repositoryClass: UsuarioRolePermisoRepository::class)]
#[ORM\Table(name: 'usuario_role_permiso', schema: 'gbl')]
class UsuarioRolePermiso
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RolePermiso::class)]
    #[ORM\JoinColumn(name: 'role_permiso_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RolePermiso $rolePermiso;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Usuario $usuario, RolePermiso $rolePermiso)
    {
        $this->usuario = $usuario;
        $this->rolePermiso = $rolePermiso;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRolePermiso(): RolePermiso
    {
        return $this->rolePermiso;
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
}
