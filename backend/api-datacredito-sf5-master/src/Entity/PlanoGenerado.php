<?php

namespace App\Entity;

use App\Repository\PlanoGeneradoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Auditoría regulatoria: una fila por cada archivo (TXT/Excel/plano-Excel)
 * generado para un período — evidencia de qué se generó, cuándo, quién lo
 * pidió y con qué contenido exacto (`hashContenido`, SHA-256). Corrige el
 * bug legacy #6 (sin auditoría de quién cerró/editó) para el paso final
 * del flujo: la generación del archivo que efectivamente se entrega a la
 * central de riesgo.
 */
#[ORM\Entity(repositoryClass: PlanoGeneradoRepository::class)]
#[ORM\Table(name: 'plano_generado', schema: 'datacredito')]
class PlanoGenerado
{
    public const TIPO_TXT = 'txt';
    public const TIPO_EXCEL = 'excel';
    public const TIPO_PLANO_EXCEL = 'plano_excel';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PeriodoCierre::class)]
    #[ORM\JoinColumn(name: 'periodo_cierre_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PeriodoCierre $periodoCierre;

    #[ORM\Column(type: 'string', length: 20)]
    private string $tipo;

    /** FK cruda → gbl.empleado.id (mismo patrón que PeriodoCierre::$empleadoCierreId/Reclasificacion::$empleadoId). ON DELETE RESTRICT: evidencia regulatoria, no debe poder perder su autor. */
    #[ORM\Column(name: 'empleado_id', type: 'integer')]
    private int $empleadoId;

    #[ORM\Column(name: 'numero_registros', type: 'integer')]
    private int $numeroRegistros;

    /** SHA-256 del contenido generado — evidencia de qué se envió/descargó exactamente. */
    #[ORM\Column(name: 'hash_contenido', type: 'string', length: 64)]
    private string $hashContenido;

    #[ORM\Column(name: 'nombre_archivo', type: 'string', length: 255)]
    private string $nombreArchivo;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(
        PeriodoCierre $periodoCierre,
        string $tipo,
        int $empleadoId,
        int $numeroRegistros,
        string $hashContenido,
        string $nombreArchivo,
    ) {
        $this->periodoCierre = $periodoCierre;
        $this->tipo = $tipo;
        $this->empleadoId = $empleadoId;
        $this->numeroRegistros = $numeroRegistros;
        $this->hashContenido = $hashContenido;
        $this->nombreArchivo = $nombreArchivo;
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

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function getEmpleadoId(): int
    {
        return $this->empleadoId;
    }

    public function getNumeroRegistros(): int
    {
        return $this->numeroRegistros;
    }

    public function getHashContenido(): string
    {
        return $this->hashContenido;
    }

    public function getNombreArchivo(): string
    {
        return $this->nombreArchivo;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
