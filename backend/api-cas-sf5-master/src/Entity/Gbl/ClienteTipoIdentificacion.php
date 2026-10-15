<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\ClienteTipoIdentificacionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Catálogo local (no sincronizado desde NetSuite) que normaliza lo que
 * antes era `Cliente::$tipoIdentificacion` (varchar suelto). `codigo` es el
 * código DataCrédito ('N'/'C') que ya consumían GeneradorExcelService (columna
 * TIPO), GeneradorTxtService (campo TIPO_IDENTIFICACION del plano oficial) y
 * Cliente::getNombreMostrar() — sigue siendo esa la columna de negocio, ahora
 * respaldada por una fila de catálogo en vez de un string libre.
 *
 * Sembrado por migración con los dos valores conocidos (NIT/N, CEDULA/C);
 * `estado` permite deshabilitar un tipo sin borrarlo (ej. si DataCrédito dejara
 * de aceptarlo) sin romper las filas de `cliente` que ya lo referencian.
 */
#[ORM\Entity(repositoryClass: ClienteTipoIdentificacionRepository::class)]
#[ORM\Table(name: 'cliente_tipo_identificacion', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_cliente_tipo_identificacion_codigo', columns: ['codigo'])]
class ClienteTipoIdentificacion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', options: ['comment' => 'Identificador surrogate — las FK de gbl.cliente apuntan aquí, nunca a codigo.'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, options: ['comment' => 'Nombre legible del tipo de identificación, ej. "NIT", "CEDULA".'])]
    private string $descripcion;

    /** Código DataCrédito ('N'/'C') — ver docblock de la clase sobre dónde se consume. */
    #[ORM\Column(type: 'string', length: 10, options: ['comment' => 'Código DataCrédito equivalente (N/C) — el que se exporta en reportes y plano oficial, no el id surrogate.'])]
    private string $codigo;

    #[ORM\Column(type: 'boolean', options: ['default' => true, 'comment' => 'Permite deshabilitar un tipo sin borrar la fila ni romper las FK de cliente que ya lo referencian.'])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable', options: ['comment' => 'Fecha de alta del tipo en el catálogo (sembrado por migración, no editado en runtime salvo estado).'])]
    private \DateTimeImmutable $creadoEn;

    public function __construct(string $descripcion, string $codigo)
    {
        $this->descripcion = $descripcion;
        $this->codigo = $codigo;
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

    public function getCodigo(): string
    {
        return $this->codigo;
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
