<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\RolePermisoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.role_permiso — propiedad de API users sf4-1, solo lectura aquí
 * (ver dbal.schema_filter). Jerarquía auto-referenciada: asignarle a un
 * usuario el nodo padre le da también todo lo que cuelga debajo (ver
 * PermisosEfectivosService).
 */
#[ORM\Entity(repositoryClass: RolePermisoRepository::class)]
#[ORM\Table(name: 'role_permiso', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_role_permiso_role', columns: ['role'])]
class RolePermiso
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $role;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'hijos')]
    #[ORM\JoinColumn(name: 'role_padre_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?RolePermiso $rolePadre = null;

    /** @var Collection<int, RolePermiso> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'rolePadre')]
    private Collection $hijos;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct()
    {
        $this->hijos = new ArrayCollection();
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getRolePadre(): ?RolePermiso
    {
        return $this->rolePadre;
    }

    public function setRolePadre(?RolePermiso $rolePadre): static
    {
        $this->rolePadre = $rolePadre;

        return $this;
    }

    /** @return Collection<int, RolePermiso> */
    public function getHijos(): Collection
    {
        return $this->hijos;
    }

    /** Profundidad en la jerarquía: 1 = raíz (rolePadre null), 2 = hijo de una raíz, etc. */
    public function nivel(): int
    {
        return $this->rolePadre === null ? 1 : 1 + $this->rolePadre->nivel();
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
