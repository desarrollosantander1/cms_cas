<?php

namespace App\Entity;

use App\Repository\PeriodoCierreRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un período de reporte (cierre mensual). Solo puede crearse (fecha_reporte
 * única) y cerrarse una vez — no hay "reabrir". `empleadoCierreId` es una FK
 * cruda (no asociación Doctrine) hacia gbl.empleado.id, un esquema que este
 * servicio nunca migra — la restricción FK real se agrega a mano en la
 * migración (ver Fase 0 / dbal.schema_filter).
 */
#[ORM\Entity(repositoryClass: PeriodoCierreRepository::class)]
#[ORM\Table(name: 'periodo_cierre', schema: 'datacredito')]
#[ORM\UniqueConstraint(name: 'uq_periodo_cierre_fecha_reporte', columns: ['fecha_reporte'])]
class PeriodoCierre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'fecha_reporte', type: 'date_immutable')]
    private \DateTimeImmutable $fechaReporte;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $abierto = true;

    /** FK cruda → gbl.empleado.id, nullable hasta que se cierre. */
    #[ORM\Column(name: 'empleado_cierre_id', type: 'integer', nullable: true)]
    private ?int $empleadoCierreId = null;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column(name: 'modificado_en', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $modificadoEn = null;

    public function __construct(\DateTimeImmutable $fechaReporte)
    {
        $this->fechaReporte = $fechaReporte;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFechaReporte(): \DateTimeImmutable
    {
        return $this->fechaReporte;
    }

    public function isAbierto(): bool
    {
        return $this->abierto;
    }

    public function getEmpleadoCierreId(): ?int
    {
        return $this->empleadoCierreId;
    }

    public function cerrar(int $empleadoId): static
    {
        $this->abierto = false;
        $this->empleadoCierreId = $empleadoId;
        $this->modificadoEn = new \DateTimeImmutable();

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function getModificadoEn(): ?\DateTimeImmutable
    {
        return $this->modificadoEn;
    }
}
