<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ProductoTipoComboRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.producto_tipo_combo — dueño y migrador: este microservicio
 * (tabla creada en Version20260912120100 junto al resto del catálogo de
 * producto). Catálogo de tipos de combo de un producto; lo referencia
 * gbl.producto de forma opcional (columna `tipo_combo_id`, nullable — un
 * producto normal no es un combo).
 *
 * Todas las columnas de negocio son nullable en BD (a diferencia de
 * marca/linea/abc): el catálogo admite filas incompletas.
 */
#[ORM\Entity(repositoryClass: ProductoTipoComboRepository::class)]
#[ORM\Table(name: 'producto_tipo_combo', schema: 'gbl')]
class ProductoTipoCombo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteId = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

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

    public function getNetsuiteId(): ?string
    {
        return $this->netsuiteId;
    }

    public function setNetsuiteId(?string $netsuiteId): static
    {
        $this->netsuiteId = $netsuiteId;

        return $this;
    }

    public function getDescripcion(): ?string
    {
        return $this->descripcion;
    }

    public function setDescripcion(?string $descripcion): static
    {
        $this->descripcion = $descripcion;

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
