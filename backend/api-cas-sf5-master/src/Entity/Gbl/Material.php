<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\MaterialRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.material — dueño y migrador: este microservicio (tabla creada
 * en Version20260929150000 junto al resto de tablas de centros de servicio,
 * ver .scratch/cas/tablas-logica-cas-parte-2.sql). Catálogo de materiales de
 * un producto (ej. "ACERO", "PLASTICO"); lo asocia gbl.producto_material
 * (ver ProductoMaterial), que admite varios materiales por producto con su
 * porcentaje de composición.
 *
 * `netsuiteId` y `descripcion` son nullable porque así están las columnas
 * reales en BD.
 */
#[ORM\Entity(repositoryClass: MaterialRepository::class)]
#[ORM\Table(name: 'material', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_material_netsuite_id', columns: ['netsuite_id'])]
class Material
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
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
