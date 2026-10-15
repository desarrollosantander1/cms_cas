<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ImpuestoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.impuesto — dueño y migrador: este microservicio (tabla creada en
 * Version20260912120100 junto al resto del catálogo de producto). Catálogo
 * de impuestos (ej. IVA); cada tarifa concreta vive en gbl.impuesto_config
 * (ver ImpuestoConfig), que es lo que realmente termina asociado a un
 * producto vía gbl.producto_impuesto (ver ProductoImpuesto).
 *
 * A diferencia de marca/linea/abc, aquí `estado` es varchar (no boolean) y
 * todas las columnas de negocio son nullable en BD — mismo criterio real de
 * la tabla, no una convención elegida por esta entidad.
 */
#[ORM\Entity(repositoryClass: ImpuestoRepository::class)]
#[ORM\Table(name: 'impuesto', schema: 'gbl')]
class Impuesto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteId = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $descripcion = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $estado = null;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $creadoEn = null;

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

    public function getEstado(): ?string
    {
        return $this->estado;
    }

    public function setEstado(?string $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getCreadoEn(): ?\DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function setCreadoEn(?\DateTimeImmutable $creadoEn): static
    {
        $this->creadoEn = $creadoEn;

        return $this;
    }
}
