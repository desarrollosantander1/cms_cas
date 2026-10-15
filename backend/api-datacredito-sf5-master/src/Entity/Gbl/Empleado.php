<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\EmpleadoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.empleado — propiedad de API users sf4-1 (dueño de su CRUD y sus
 * migraciones). Este servicio SOLO lee esta tabla, nunca la migra (ver
 * dbal.schema_filter en config/packages/doctrine.yaml): la necesita para
 * resolver el empleado del usuario autenticado y así poblar las FK
 * empleado_id de periodo_cierre / reclasificacion / plano_generado.
 */
#[ORM\Entity(repositoryClass: EmpleadoRepository::class)]
#[ORM\Table(name: 'empleado', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_empleado_usuario', columns: ['usuario_id'])]
class Empleado
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\Column(name: 'netsuite_empleado_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteEmpleadoId = null;

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
}
