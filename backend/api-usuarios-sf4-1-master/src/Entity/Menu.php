<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\Table(name: 'menu', schema: 'gbl')]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: MenuGrupo::class)]
    #[ORM\JoinColumn(name: 'menu_grupo_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private MenuGrupo $menuGrupo;

    #[ORM\Column(name: 'menu', type: 'string', length: 255)]
    private string $menu;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'hijos')]
    #[ORM\JoinColumn(name: 'menu_padre_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?Menu $menuPadre = null;

    /** @var Collection<int, Menu> */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'menuPadre')]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $hijos;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $icono = null;

    /** Aditivo sobre el esquema pasado — sin esto el sidebar no tiene a dónde navegar. */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ruta = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    /** Posición entre "hermanos" (mismo menu_padre_id, o mismo menu_grupo_id si es de primer nivel). */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $orden = 0;

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

    public function getMenuGrupo(): MenuGrupo
    {
        return $this->menuGrupo;
    }

    public function setMenuGrupo(MenuGrupo $menuGrupo): static
    {
        $this->menuGrupo = $menuGrupo;

        return $this;
    }

    public function getMenu(): string
    {
        return $this->menu;
    }

    public function setMenu(string $menu): static
    {
        $this->menu = $menu;

        return $this;
    }

    public function getMenuPadre(): ?Menu
    {
        return $this->menuPadre;
    }

    public function setMenuPadre(?Menu $menuPadre): static
    {
        $this->menuPadre = $menuPadre;

        return $this;
    }

    /** @return Collection<int, Menu> */
    public function getHijos(): Collection
    {
        return $this->hijos;
    }

    public function getIcono(): ?string
    {
        return $this->icono;
    }

    public function setIcono(?string $icono): static
    {
        $this->icono = $icono;

        return $this;
    }

    public function getRuta(): ?string
    {
        return $this->ruta;
    }

    public function setRuta(?string $ruta): static
    {
        $this->ruta = $ruta;

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
