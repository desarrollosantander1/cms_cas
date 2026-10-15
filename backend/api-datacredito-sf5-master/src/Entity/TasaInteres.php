<?php

namespace App\Entity;

use App\Repository\TasaInteresRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tasa de interés de mora mensual usada por el Generador de Cartas jurídicas.
 * Reemplaza la constante hardcodeada CalculadorInteres::TASA_MENSUAL_DEFECTO.
 * Porta CRM_CARTERA_TASA_INTERES del legacy: cada cambio es una fila nueva
 * (histórico completo de quién cambió qué y cuándo); la tasa vigente es
 * simplemente la más reciente — no hay bandera "activa" que mantener
 * sincronizada (ver TasaInteresRepository::vigente()).
 *
 * Tabla report-specific → esquema `datacredito` (como periodo_cierre,
 * reclasificacion, plano_generado). `empleadoId` es FK cruda a gbl.empleado
 * con ON DELETE RESTRICT — igual que Reclasificacion: perder el rastro de
 * quién fijó una tasa de mora es peor que bloquear el borrado del empleado.
 */
#[ORM\Entity(repositoryClass: TasaInteresRepository::class)]
#[ORM\Table(name: 'tasa_interes', schema: 'datacredito')]
class TasaInteres
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /** Porcentaje mensual (2.000 = 2% mensual). */
    #[ORM\Column(name: 'tasa_mensual', type: 'decimal', precision: 6, scale: 3)]
    private string $tasaMensual;

    /** FK cruda → gbl.empleado.id (igual que Reclasificacion::$empleadoId). */
    #[ORM\Column(name: 'empleado_id', type: 'integer')]
    private int $empleadoId;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(string $tasaMensual, int $empleadoId)
    {
        $this->tasaMensual = $tasaMensual;
        $this->empleadoId = $empleadoId;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTasaMensual(): string
    {
        return $this->tasaMensual;
    }

    public function getEmpleadoId(): int
    {
        return $this->empleadoId;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
