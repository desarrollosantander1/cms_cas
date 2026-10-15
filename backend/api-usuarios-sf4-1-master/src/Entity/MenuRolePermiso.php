<?php

namespace App\Entity;

use App\Repository\MenuRolePermisoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRolePermisoRepository::class)]
#[ORM\Table(name: 'menu_role_permiso', schema: 'gbl')]
class MenuRolePermiso
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RolePermiso::class)]
    #[ORM\JoinColumn(name: 'role_permiso_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private RolePermiso $rolePermiso;

    #[ORM\ManyToOne(targetEntity: Menu::class)]
    #[ORM\JoinColumn(name: 'menu_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Menu $menu;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Menu $menu, RolePermiso $rolePermiso)
    {
        $this->menu = $menu;
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

    public function getMenu(): Menu
    {
        return $this->menu;
    }

    public function isEstado(): bool
    {
        return $this->estado;
    }
}
