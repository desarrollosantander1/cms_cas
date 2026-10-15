<?php

namespace App\Entity\Gbl;

use App\Repository\Gbl\FacturaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Capa espejo: copia local de una factura de NetSuite, poblada por
 * IngestaFacturaService vía POST /sync/facturas. `saldoActual` es el
 * amountRemaining que NetSuite ya expone directamente — a propósito NO hay
 * ningún CalculadorSaldo aquí: el legacy reconstruía el saldo encadenando
 * CRM_CARTERA → CRM_CARTERA_DETALLE → CRM_CARTERA_CRUCE en ~3 CTEs por
 * consulta, y ese cálculo desaparece por completo en este diseño.
 *
 * Vive en `gbl` (dato maestro compartido, ver Cliente) pero sigue siendo
 * propiedad de `datacredito`. No tiene FK propia a `Cliente`: se llega a él
 * transitivamente vía `clienteSucursal` (obligatoria) — `cliente_sucursal`
 * ya relaciona con `cliente`, así que duplicar esa FK en `factura` sería
 * redundante. A diferencia del diseño original de esta entidad, aquí NO se
 * tolera una factura sin sucursal (el legacy sí, vía LEFT JOIN + fallback a
 * CLIENTE_ID) — se decidió exigir sucursal siempre para no perder la única
 * vía de resolver el cliente de la factura.
 */
#[ORM\Entity(repositoryClass: FacturaRepository::class)]
#[ORM\Table(name: 'factura', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_factura_netsuite_id', columns: ['netsuite_id'])]
#[ORM\UniqueConstraint(name: 'uq_factura_comprobante_numero', columns: ['comprobante', 'numero'])]
class Factura
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'netsuite_id', type: 'string', length: 255)]
    private string $netsuiteId;

    #[ORM\ManyToOne(targetEntity: ClienteSucursal::class)]
    #[ORM\JoinColumn(name: 'cliente_sucursal_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ClienteSucursal $clienteSucursal;

    /**
     * Antes un solo campo `numeroComprobante` — separado en `comprobante` +
     * `numero` para reflejar fielmente CRM_FACTURA.COMPROBANTE/NUMERO del
     * legacy, que el plano concatena (`cf.COMPROBANTE || cf.NUMERO`, ver
     * GeneradorTxtService). El orden de las columnas en la tabla es
     * deliberado: comprobante primero, numero después.
     */
    #[ORM\Column(type: 'string', length: 50)]
    private string $comprobante;

    #[ORM\Column(type: 'string', length: 50)]
    private string $numero;

    #[ORM\Column(name: 'fecha_emision', type: 'date_immutable')]
    private \DateTimeImmutable $fechaEmision;

    #[ORM\Column(name: 'plazo_pago_dias', type: 'smallint', nullable: true)]
    private ?int $plazoPagoDias = null;

    /**
     * Normalizado en gbl.forma_pago (antes smallint suelto). Filtra qué
     * facturas entran al módulo de datacrédito: solo Crédito se reporta —
     * ver FacturaRepository::findByCliente().
     */
    #[ORM\ManyToOne(targetEntity: FormaPago::class)]
    #[ORM\JoinColumn(name: 'forma_pago_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?FormaPago $formaPago = null;

    #[ORM\Column(type: 'decimal', precision: 18, scale: 2)]
    private string $total;

    /** amountRemaining de NetSuite — snapshot vivo del saldo, sincronizado en cada ingesta. */
    #[ORM\Column(name: 'saldo_actual', type: 'decimal', precision: 18, scale: 2)]
    private string $saldoActual;

    /** open / paid / voided — estado tal cual lo reporta NetSuite. */
    #[ORM\Column(name: 'estado_ns', type: 'string', length: 50, nullable: true)]
    private ?string $estadoNs = null;

    #[ORM\Column(name: 'sincronizado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $sincronizadoEn;

    public function __construct(
        string $netsuiteId,
        ClienteSucursal $clienteSucursal,
        string $comprobante,
        string $numero,
        \DateTimeImmutable $fechaEmision,
        string $total,
        string $saldoActual,
    ) {
        $this->netsuiteId = $netsuiteId;
        $this->clienteSucursal = $clienteSucursal;
        $this->comprobante = $comprobante;
        $this->numero = $numero;
        $this->fechaEmision = $fechaEmision;
        $this->total = $total;
        $this->saldoActual = $saldoActual;
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

    /** Transitivo vía clienteSucursal — factura no tiene FK propia a Cliente (ver docblock de la clase). */
    public function getCliente(): Cliente
    {
        return $this->clienteSucursal->getCliente();
    }

    public function getClienteSucursal(): ClienteSucursal
    {
        return $this->clienteSucursal;
    }

    public function setClienteSucursal(ClienteSucursal $clienteSucursal): static
    {
        $this->clienteSucursal = $clienteSucursal;

        return $this;
    }

    public function getComprobante(): string
    {
        return $this->comprobante;
    }

    public function setComprobante(string $comprobante): static
    {
        $this->comprobante = $comprobante;

        return $this;
    }

    public function getNumero(): string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    /** cf.COMPROBANTE || cf.NUMERO del legacy — identificador de negocio de la factura, usado en el plano/Excel. */
    public function getNumeroComprobante(): string
    {
        return $this->comprobante.$this->numero;
    }

    public function getFechaEmision(): \DateTimeImmutable
    {
        return $this->fechaEmision;
    }

    public function setFechaEmision(\DateTimeImmutable $fechaEmision): static
    {
        $this->fechaEmision = $fechaEmision;

        return $this;
    }

    public function getPlazoPagoDias(): ?int
    {
        return $this->plazoPagoDias;
    }

    public function setPlazoPagoDias(?int $plazoPagoDias): static
    {
        $this->plazoPagoDias = $plazoPagoDias;

        return $this;
    }

    public function getFormaPago(): ?FormaPago
    {
        return $this->formaPago;
    }

    public function setFormaPago(?FormaPago $formaPago): static
    {
        $this->formaPago = $formaPago;

        return $this;
    }

    public function getTotal(): string
    {
        return $this->total;
    }

    public function setTotal(string $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getSaldoActual(): string
    {
        return $this->saldoActual;
    }

    public function setSaldoActual(string $saldoActual): static
    {
        $this->saldoActual = $saldoActual;

        return $this;
    }

    public function getEstadoNs(): ?string
    {
        return $this->estadoNs;
    }

    public function setEstadoNs(?string $estadoNs): static
    {
        $this->estadoNs = $estadoNs;

        return $this;
    }

    public function getSincronizadoEn(): \DateTimeImmutable
    {
        return $this->sincronizadoEn;
    }

    public function tocarSincronizado(): static
    {
        $this->sincronizadoEn = new \DateTimeImmutable();

        return $this;
    }
}
