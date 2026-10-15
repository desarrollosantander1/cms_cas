<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\MenuRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.menu — propiedad de API users sf4-1, solo lectura aquí (ver
 * dbal.schema_filter). Mapeo deliberadamente mínimo: solo lo que necesita
 * la regla de "rol bonificado por menú" (ver PermisosEfectivosService) —
 * omite menu_grupo, ruta, icono, orden, que no hacen falta aquí y evitarían
 * tener que mapear también Gbl\MenuGrupo solo para esto.
 */
#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\Table(name: 'menu', schema: 'gbl')]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'menu_padre_id', referencedColumnName: 'id', nullable: true)]
    private ?Menu $menuPadre = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMenuPadre(): ?self
    {
        return $this->menuPadre;
    }

    public function isEstado(): bool
    {
        return $this->estado;
    }
}
