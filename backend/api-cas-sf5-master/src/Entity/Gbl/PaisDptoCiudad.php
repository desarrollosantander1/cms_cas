<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\PaisDptoCiudadRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Capa espejo: ciudad de NetSuite, poblada por IngestaPaisDptoCiudadService vía
 * POST /sync/ciudades. Hoja de la jerarquía pais -> pais_dpto -> pais_dpto_ciudad
 * — es lo que ClienteSucursal referencia para su dirección (antes guardaba
 * ciudad_nombre/ciudad_codigo_dane/departamento_nombre como texto suelto,
 * repetido por cada sucursal en vez de normalizado una sola vez aquí).
 * `codigoDane` es el código DANE colombiano (ver CRM_CIUDAD.CODIGO del
 * legacy) — nullable porque el catálogo geográfico no es exclusivamente
 * colombiano.
 */
#[ORM\Entity(repositoryClass: PaisDptoCiudadRepository::class)]
#[ORM\Table(name: 'pais_dpto_ciudad', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_pais_dpto_ciudad_netsuite_id', columns: ['netsuite_id'])]
class PaisDptoCiudad
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255)]
    private string $netsuiteId;

    #[ORM\ManyToOne(targetEntity: PaisDpto::class)]
    #[ORM\JoinColumn(name: 'pais_dpto_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private PaisDpto $paisDpto;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nombre;

    #[ORM\Column(name: 'codigo_dane', type: 'string', length: 20, nullable: true)]
    private ?string $codigoDane = null;

    #[ORM\Column(name: 'sincronizado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $sincronizadoEn;

    public function __construct(string $netsuiteId, PaisDpto $paisDpto, string $nombre)
    {
        $this->netsuiteId = $netsuiteId;
        $this->paisDpto = $paisDpto;
        $this->nombre = $nombre;
        $this->sincronizadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNetsuiteId(): string
    {
        return $this->netsuiteId;
    }

    public function getPaisDpto(): PaisDpto
    {
        return $this->paisDpto;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getCodigoDane(): ?string
    {
        return $this->codigoDane;
    }

    public function setCodigoDane(?string $codigoDane): static
    {
        $this->codigoDane = $codigoDane;

        return $this;
    }

    public function tocarSincronizado(): static
    {
        $this->sincronizadoEn = new \DateTimeImmutable();

        return $this;
    }
}
