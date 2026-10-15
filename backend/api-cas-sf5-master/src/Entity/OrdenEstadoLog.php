<?php

namespace App\Entity;

use App\Entity\Gbl\Usuario;
use App\Repository\OrdenEstadoLogRepository;
use Doctrine\ORM\Mapping as ORM;

/** Mapea cas.orden_estado_log — dueño y migrador: este microservicio. Historial de cambios de estado de una orden (quién y cuándo). */
#[ORM\Entity(repositoryClass: OrdenEstadoLogRepository::class)]
#[ORM\Table(name: 'orden_estado_log', schema: 'cas')]
class OrdenEstadoLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Orden::class)]
    #[ORM\JoinColumn(name: 'orden_id', referencedColumnName: 'id', nullable: false)]
    private Orden $orden;

    #[ORM\ManyToOne(targetEntity: OrdenEstado::class)]
    #[ORM\JoinColumn(name: 'orden_estado_id', referencedColumnName: 'id', nullable: false)]
    private OrdenEstado $ordenEstado;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false)]
    private Usuario $usuario;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Orden $orden, OrdenEstado $ordenEstado, Usuario $usuario)
    {
        $this->orden = $orden;
        $this->ordenEstado = $ordenEstado;
        $this->usuario = $usuario;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrden(): Orden
    {
        return $this->orden;
    }

    public function getOrdenEstado(): OrdenEstado
    {
        return $this->ordenEstado;
    }

    public function getUsuario(): Usuario
    {
        return $this->usuario;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
