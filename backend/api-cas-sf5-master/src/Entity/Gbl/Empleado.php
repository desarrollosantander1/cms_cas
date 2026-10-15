<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\EmpleadoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.empleado — espejo de solo lectura, propiedad de otro
 * microservicio (mismo patrón que gbl.role_permiso/gbl.usuario, ver
 * dbal.schema_filter): este servicio no lo migra ni lo crea, solo lo
 * referencia desde gbl.producto_repuesto.empleado_id (quién registró el
 * repuesto de un producto compuesto).
 *
 * Mapeo mínimo: no hace falta más que `usuario` (para trazabilidad) y
 * `netsuiteEmpleadoId`. Nunca se instancia desde este microservicio.
 */
#[ORM\Entity(repositoryClass: EmpleadoRepository::class)]
#[ORM\Table(name: 'empleado', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_empleado_usuario', columns: ['usuario_id'])]
#[ORM\UniqueConstraint(name: 'uq_empleado_netsuite_empleado_id', columns: ['netsuite_empleado_id'])]
class Empleado
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\Column(name: 'netsuite_empleado_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteEmpleadoId = null;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): Usuario
    {
        return $this->usuario;
    }

    public function getNetsuiteEmpleadoId(): ?string
    {
        return $this->netsuiteEmpleadoId;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
