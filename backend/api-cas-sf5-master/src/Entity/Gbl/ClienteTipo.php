<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ClienteTipoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Catálogo local (no sincronizado desde NetSuite) que normaliza lo que antes
 * eran dos columnas sueltas de `Cliente`: `tipoCliente` (smallint sin ningún
 * consumidor) y `tipoTercero` (smallint, único uso real: el filtro
 * "nacional/extranjero" de `ClienteRepository::findElegibles()`). Esta tabla
 * absorbe esa responsabilidad — `tipoCliente` pasa a ser la FK aquí y
 * `tipoTercero` desaparece — por `descripcion`, no por un id mágico:
 * `findElegibles()` filtra por `descripcion = 'NACIONAL'`, nunca por
 * `tipoCliente = 1`.
 *
 * Sembrado por migración con los dos valores conocidos (NACIONAL/EXTRANJERO);
 * `estado` permite deshabilitar uno sin borrarlo ni romper las FK existentes.
 */
#[ORM\Entity(repositoryClass: ClienteTipoRepository::class)]
#[ORM\Table(name: 'cliente_tipo', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_cliente_tipo_descripcion', columns: ['descripcion'])]
class ClienteTipo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['comment' => 'Identificador surrogate — las FK de gbl.cliente apuntan aquí.'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, options: ['comment' => 'Nombre del tipo de cliente, ej. "NACIONAL", "EXTRANJERO" — findElegibles() filtra por este valor, no por el id.'])]
    private string $descripcion;

    #[ORM\Column(type: 'boolean', options: ['default' => true, 'comment' => 'Permite deshabilitar un tipo sin borrar la fila ni romper las FK de cliente que ya lo referencian.'])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable', options: ['comment' => 'Fecha de alta del tipo en el catálogo (sembrado por migración, no editado en runtime salvo estado).'])]
    private \DateTimeImmutable $creadoEn;

    public function __construct(string $descripcion)
    {
        $this->descripcion = $descripcion;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function isEstado(): bool
    {
        return $this->estado;
    }

    public function setEstado(bool $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
