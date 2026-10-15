<?php

namespace App\Entity;

use App\Repository\ConfiguracionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Parámetros de negocio/institucionales del módulo DataCrédito, antes
 * quemados como constantes de clase (código de suscriptor DataCrédito,
 * textos institucionales del correo de cartas jurídicas, umbrales de
 * novedad/vencimiento) — ver auditoría "Parámetros DataCrédito" y
 * ConfiguracionService, que resuelve codigo -> valor.
 *
 * Clave-valor simple, no histórica como TasaInteres: ninguno de estos
 * valores necesita reconstruirse a una fecha pasada (el plano ya generado
 * queda íntegro en PlanoGenerado, las cartas ya enviadas quedan como el PDF
 * adjunto) — solo se guarda el valor vigente y quién lo cambió por última
 * vez. `valor` siempre es string; cada llamador castea según lo que espera
 * (ver ConfiguracionService).
 */
#[ORM\Entity(repositoryClass: ConfiguracionRepository::class)]
#[ORM\Table(name: 'configuracion', schema: 'datacredito')]
#[ORM\UniqueConstraint(name: 'uq_configuracion_codigo', columns: ['codigo'])]
class Configuracion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 60)]
    private string $codigo;

    /** Sin límite de longitud (ver migración que amplía la columna): aquí viven también los párrafos completos de las cartas jurídicas PDF. */
    #[ORM\Column(type: 'string')]
    private string $valor;

    #[ORM\Column(type: 'string', length: 255)]
    private string $descripcion;

    /** FK cruda → gbl.empleado.id (mismo patrón que TasaInteres::$empleadoId). Nullable: el seed de sistema no tiene autor humano. */
    #[ORM\Column(name: 'modificado_por_id', type: 'integer', nullable: true)]
    private ?int $modificadoPorId = null;

    /** DB rellena el valor por defecto (NOW()) si la app no lo fija explícito (ej. seed por SQL directo). */
    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column(name: 'modificado_en', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $modificadoEn = null;

    public function __construct(string $codigo, string $valor, string $descripcion)
    {
        $this->codigo = $codigo;
        $this->valor = $valor;
        $this->descripcion = $descripcion;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function getValor(): string
    {
        return $this->valor;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function getModificadoPorId(): ?int
    {
        return $this->modificadoPorId;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function getModificadoEn(): ?\DateTimeImmutable
    {
        return $this->modificadoEn;
    }

    public function actualizar(string $valor, int $empleadoId): void
    {
        $this->valor = $valor;
        $this->modificadoPorId = $empleadoId;
        $this->modificadoEn = new \DateTimeImmutable();
    }
}
