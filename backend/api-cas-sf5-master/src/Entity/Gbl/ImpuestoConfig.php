<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ImpuestoConfigRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.impuesto_config — dueño y migrador: este microservicio (tabla
 * creada en Version20260912120100 junto al resto del catálogo de producto).
 * Tarifa concreta de un gbl.impuesto (ver Impuesto), ej. "IVA 19%"
 * (`valor` = 19.0000); es esta fila, no el impuesto genérico, la que
 * gbl.producto_impuesto asocia a un producto (ver ProductoImpuesto).
 *
 * `impuestoId` es nullable porque así está la FK real en BD (sin NOT NULL);
 * todas las demás columnas de negocio también son nullable.
 */
#[ORM\Entity(repositoryClass: ImpuestoConfigRepository::class)]
#[ORM\Table(name: 'impuesto_config', schema: 'gbl')]
class ImpuestoConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteId = null;

    #[ORM\ManyToOne(targetEntity: Impuesto::class)]
    #[ORM\JoinColumn(name: 'impuesto_id', referencedColumnName: 'id', nullable: true)]
    private ?Impuesto $impuesto = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $descripcion = null;

    /** numeric(12,4) — como string para no perder precisión (convención estándar de Doctrine para `decimal`). */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 4, nullable: true)]
    private ?string $valor = null;

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

    public function getImpuesto(): ?Impuesto
    {
        return $this->impuesto;
    }

    public function setImpuesto(?Impuesto $impuesto): static
    {
        $this->impuesto = $impuesto;

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

    public function getValor(): ?string
    {
        return $this->valor;
    }

    public function setValor(?string $valor): static
    {
        $this->valor = $valor;

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
