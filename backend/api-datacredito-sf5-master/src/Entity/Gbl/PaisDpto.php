<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\PaisDptoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Capa espejo: departamento/estado de NetSuite, poblado por
 * IngestaPaisDptoService vía POST /sync/departamentos. Nivel intermedio de
 * la jerarquía pais -> pais_dpto -> pais_dpto_ciudad (ver Pais/ClienteSucursal).
 */
#[ORM\Entity(repositoryClass: PaisDptoRepository::class)]
#[ORM\Table(name: 'pais_dpto', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_pais_dpto_netsuite_id', columns: ['netsuite_id'])]
class PaisDpto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255)]
    private string $netsuiteId;

    #[ORM\ManyToOne(targetEntity: Pais::class)]
    #[ORM\JoinColumn(name: 'pais_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Pais $pais;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nombre;

    #[ORM\Column(name: 'sincronizado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $sincronizadoEn;

    public function __construct(string $netsuiteId, Pais $pais, string $nombre)
    {
        $this->netsuiteId = $netsuiteId;
        $this->pais = $pais;
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

    public function getPais(): Pais
    {
        return $this->pais;
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
