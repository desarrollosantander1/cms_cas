<?php

namespace App\Entity;

use App\Repository\MenuGrupoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuGrupoRepository::class)]
#[ORM\Table(name: 'menu_grupo', schema: 'gbl')]
class MenuGrupo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $grupo;

    #[ORM\Column(type: 'string', length: 255)]
    private string $icono;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    /** Posición para ordenar los grupos entre sí (menor primero). */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $orden = 0;

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

    public function getGrupo(): string
    {
        return $this->grupo;
    }

    public function setGrupo(string $grupo): static
    {
        $this->grupo = $grupo;

        return $this;
    }

    public function getIcono(): string
    {
        return $this->icono;
    }

    public function setIcono(string $icono): static
    {
        $this->icono = $icono;

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

    public function getOrden(): int
    {
        return $this->orden;
    }

    public function setOrden(int $orden): static
    {
        $this->orden = $orden;

        return $this;
    }
}
