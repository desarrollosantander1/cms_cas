<?php

namespace App\Entity;

use App\Entity\Gbl\Factura;
use App\Repository\ReclasificacionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Marca una factura como excluida del flujo normal desde un período dado en
 * adelante — a partir de ahí se reporta con novedad editable a mano (ver
 * PeriodoFacturaService::ModoNovedad::MANUAL), no auto-calculada. Se crea
 * (o se reutiliza si ya existía) la primera vez que se guarda vía el flujo
 * de reclasificados — ver ReclasificacionService.
 */
#[ORM\Entity(repositoryClass: ReclasificacionRepository::class)]
#[ORM\Table(name: 'reclasificacion', schema: 'datacredito')]
class Reclasificacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Factura::class)]
    #[ORM\JoinColumn(name: 'factura_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Factura $factura;

    #[ORM\ManyToOne(targetEntity: PeriodoCierre::class)]
    #[ORM\JoinColumn(name: 'periodo_cierre_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PeriodoCierre $periodoCierre;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $motivo = null;

    /** FK cruda → gbl.empleado.id (igual que PeriodoCierre::$empleadoCierreId). */
    #[ORM\Column(name: 'empleado_id', type: 'integer')]
    private int $empleadoId;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Factura $factura, PeriodoCierre $periodoCierre, int $empleadoId, ?string $motivo = null)
    {
        $this->factura = $factura;
        $this->periodoCierre = $periodoCierre;
        $this->empleadoId = $empleadoId;
        $this->motivo = $motivo;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFactura(): Factura
    {
        return $this->factura;
    }

    public function getPeriodoCierre(): PeriodoCierre
    {
        return $this->periodoCierre;
    }

    public function getMotivo(): ?string
    {
        return $this->motivo;
    }

    public function getEmpleadoId(): int
    {
        return $this->empleadoId;
    }
}
