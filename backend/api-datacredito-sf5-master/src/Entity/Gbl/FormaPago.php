<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\FormaPagoRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Catálogo de formas de pago de factura — a diferencia de Pais/PaisDpto/etc.
 * (espejo puro de NetSuite, siempre poblado por /sync/*), esta tabla nace
 * sembrada a mano con los dos valores conocidos (Contado/Crédito) porque
 * todavía no existe un /sync/formas-pago; `idNetsuite` queda nullable a
 * propósito para cuando SÍ llegue esa sincronización — ver
 * IngestaFacturaService, que ya resuelve gbl.factura.forma_pago_id contra
 * esta tabla por idNetsuite.
 */
#[ORM\Entity(repositoryClass: FormaPagoRepository::class)]
#[ORM\Table(name: 'forma_pago', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_forma_pago_id_netsuite', columns: ['id_netsuite'])]
class FormaPago
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'id_netsuite', type: 'string', length: 255, nullable: true)]
    private ?string $idNetsuite = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nombre;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $prefijo = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(string $nombre, ?string $idNetsuite = null, ?string $prefijo = null)
    {
        $this->nombre = $nombre;
        $this->idNetsuite = $idNetsuite;
        $this->prefijo = $prefijo;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdNetsuite(): ?string
    {
        return $this->idNetsuite;
    }

    public function setIdNetsuite(?string $idNetsuite): static
    {
        $this->idNetsuite = $idNetsuite;

        return $this;
    }

    public function getNombre(): string
    {
        return $this->nombre;
    }

    public function getPrefijo(): ?string
    {
        return $this->prefijo;
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
