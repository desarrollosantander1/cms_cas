<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\PaisRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Capa espejo: país de NetSuite, poblado por IngestaPaisService vía
 * POST /sync/paises. Raíz de la jerarquía geográfica normalizada
 * pais -> pais_dpto -> pais_dpto_ciudad (ver ClienteSucursal, que antes
 * guardaba ciudad/departamento como texto suelto).
 */
#[ORM\Entity(repositoryClass: PaisRepository::class)]
#[ORM\Table(name: 'pais', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_pais_netsuite_id', columns: ['netsuite_id'])]
class Pais
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255)]
    private string $netsuiteId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nombre;

    #[ORM\Column(name: 'sincronizado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $sincronizadoEn;

    public function __construct(string $netsuiteId, string $nombre)
    {
        $this->netsuiteId = $netsuiteId;
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

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function tocarSincronizado(): static
    {
        $this->sincronizadoEn = new \DateTimeImmutable();

        return $this;
    }
}
