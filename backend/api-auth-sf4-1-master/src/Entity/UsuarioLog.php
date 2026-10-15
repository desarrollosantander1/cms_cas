<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.usuario_log, migrada por API users sf4-1 (dueño de ese esquema).
 * Este servicio solo escribe filas (registra intentos de login), no migra
 * la tabla.
 */
#[ORM\Entity]
#[ORM\Table(name: 'usuario_log', schema: 'gbl')]
class UsuarioLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Usuario $usuario;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $estado = null;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(Usuario $usuario, ?string $ip, ?string $estado)
    {
        $this->usuario = $usuario;
        $this->ip = $ip;
        $this->estado = $estado;
        $this->creadoEn = new \DateTimeImmutable();
    }
}
