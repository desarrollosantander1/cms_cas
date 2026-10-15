<?php

namespace App\Entity;

use App\Entity\Gbl\Cliente;
use App\Entity\Gbl\Factura;
use App\Repository\ReporteFacturaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Fotografía de una factura dentro de un período de reporte — fact table
 * clave por (periodo_cierre_id, factura_id). `saldoInicial`/`saldo` son
 * snapshots: se congelan al guardar y NUNCA se recalculan retroactivamente
 * a partir de gbl.factura (esa es la garantía de que lo ya reportado a la
 * central de riesgo es inmutable). `cliente` está desnormalizado a
 * propósito — evita un join extra en las consultas de resumen/plano, que ya
 * son las más pesadas del módulo.
 */
#[ORM\Entity(repositoryClass: ReporteFacturaRepository::class)]
#[ORM\Table(name: 'reporte_factura', schema: 'datacredito')]
#[ORM\UniqueConstraint(name: 'uq_reporte_factura_periodo_factura', columns: ['periodo_cierre_id', 'factura_id'])]
class ReporteFactura
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PeriodoCierre::class)]
    #[ORM\JoinColumn(name: 'periodo_cierre_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PeriodoCierre $periodoCierre;

    #[ORM\ManyToOne(targetEntity: Factura::class)]
    #[ORM\JoinColumn(name: 'factura_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Factura $factura;

    #[ORM\ManyToOne(targetEntity: Cliente::class)]
    #[ORM\JoinColumn(name: 'cliente_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Cliente $cliente;

    #[ORM\Column(name: 'saldo_inicial', type: 'decimal', precision: 18, scale: 2)]
    private string $saldoInicial;

    #[ORM\Column(type: 'decimal', precision: 18, scale: 2)]
    private string $saldo;

    #[ORM\ManyToOne(targetEntity: Novedad::class)]
    #[ORM\JoinColumn(name: 'novedad_id', referencedColumnName: 'id', nullable: false)]
    private Novedad $novedad;

    #[ORM\Column(name: 'dias_vencidos', type: 'smallint', options: ['default' => 0])]
    private int $diasVencidos = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $reportar = true;

    /**
     * FK cruda → gbl.empleado.id (mismo patrón que PeriodoCierre::$empleadoCierreId/
     * Reclasificacion::$empleadoId) — quién guardó/generó esta fila
     * específica. No siempre coincide con periodoCierre.empleadoCierreId:
     * una fila puede haberse guardado a mano antes del cierre (guardarUnaFactura,
     * un empleado distinto) o materializado automáticamente al cerrar
     * (materializarCandidato, el empleado que cierra).
     */
    #[ORM\Column(name: 'empleado_id', type: 'integer')]
    private int $empleadoId;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column(name: 'modificado_en', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $modificadoEn = null;

    public function __construct(
        PeriodoCierre $periodoCierre,
        Factura $factura,
        Cliente $cliente,
        string $saldoInicial,
        string $saldo,
        Novedad $novedad,
        int $diasVencidos,
        bool $reportar,
        int $empleadoId,
    ) {
        $this->periodoCierre = $periodoCierre;
        $this->factura = $factura;
        $this->cliente = $cliente;
        $this->saldoInicial = $saldoInicial;
        $this->saldo = $saldo;
        $this->novedad = $novedad;
        $this->diasVencidos = $diasVencidos;
        $this->reportar = $reportar;
        $this->empleadoId = $empleadoId;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPeriodoCierre(): PeriodoCierre
    {
        return $this->periodoCierre;
    }

    public function getFactura(): Factura
    {
        return $this->factura;
    }

    public function getCliente(): Cliente
    {
        return $this->cliente;
    }

    public function getSaldoInicial(): string
    {
        return $this->saldoInicial;
    }

    public function getSaldo(): string
    {
        return $this->saldo;
    }

    public function getNovedad(): Novedad
    {
        return $this->novedad;
    }

    public function getDiasVencidos(): int
    {
        return $this->diasVencidos;
    }

    public function isReportar(): bool
    {
        return $this->reportar;
    }

    public function getEmpleadoId(): int
    {
        return $this->empleadoId;
    }

    /**
     * Actualiza los campos editables de una fotografía ya existente en un
     * período todavía abierto. `saldoInicial` NO se toca — es el punto de
     * partida del período, fijado la primera vez que se guardó esta fila.
     * `empleadoId` SÍ se actualiza — refleja quién hizo el último guardado,
     * no quién la creó originalmente.
     */
    public function actualizar(string $saldo, Novedad $novedad, int $diasVencidos, bool $reportar, int $empleadoId): static
    {
        $this->saldo = $saldo;
        $this->novedad = $novedad;
        $this->diasVencidos = $diasVencidos;
        $this->reportar = $reportar;
        $this->empleadoId = $empleadoId;
        $this->modificadoEn = new \DateTimeImmutable();

        return $this;
    }
}
