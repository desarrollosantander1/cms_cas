<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\UnidadMedidaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea gbl.unidad_medida — dueño y migrador: este microservicio (tabla creada
 * en Version20260912120100 junto al resto del catálogo de producto). Catálogo
 * de unidades de medida de un producto (ej. "UNIDAD", "METRO", "KILOGRAMO").
 *
 * Vive en `gbl` (no `cas`) para poder reutilizarse desde otros microservicios,
 * igual que gbl.adjunto_tipo y el resto del catálogo de producto (ver
 * Entity/Gbl/Producto.php).
 *
 * `netsuiteId` es nullable porque el catálogo admite filas locales que no
 * vienen del espejo de NetSuite; `codigo` (agregado en Version20260928120000)
 * es la abreviatura corta y obligatoria de la unidad, ej. "UN", "M", "KG".
 */
#[ORM\Entity(repositoryClass: UnidadMedidaRepository::class)]
#[ORM\Table(name: 'unidad_medida', schema: 'gbl')]
class UnidadMedida
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255, nullable: true)]
    private ?string $netsuiteId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $codigo;

    #[ORM\Column(type: 'string', length: 255)]
    private string $descripcion;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(string $codigo, string $descripcion)
    {
        $this->codigo = $codigo;
        $this->descripcion = $descripcion;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNetsuiteId(): ?string
    {
        return $this->netsuiteId;
    }

    public function setNetsuiteId(?string $netsuiteId): static
    {
        $this->netsuiteId = $netsuiteId;

        return $this;
    }

    public function getCodigo(): string
    {
        return $this->codigo;
    }

    public function setCodigo(string $codigo): static
    {
        $this->codigo = $codigo;

        return $this;
    }

    public function getDescripcion(): string
    {
        return $this->descripcion;
    }

    public function setDescripcion(string $descripcion): static
    {
        $this->descripcion = $descripcion;

        return $this;
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
