<?php

namespace App\Entity;

use App\Entity\Gbl\Producto;
use App\Entity\Gbl\Usuario;
use App\Repository\OrdenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mapea cas.orden — dueño y migrador: este microservicio. Registro de una
 * compra/orden hecha por un `CasCliente` en un CAS.
 */
#[ORM\Entity(repositoryClass: OrdenRepository::class)]
#[ORM\Table(name: 'orden', schema: 'cas')]
class Orden
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cas::class)]
    #[ORM\JoinColumn(name: 'cas_id', referencedColumnName: 'id', nullable: false)]
    private Cas $cas;

    #[ORM\ManyToOne(targetEntity: CasCliente::class)]
    #[ORM\JoinColumn(name: 'cas_cliente_id', referencedColumnName: 'id', nullable: false)]
    private CasCliente $casCliente;

    #[ORM\ManyToOne(targetEntity: OrdenEstado::class)]
    #[ORM\JoinColumn(name: 'orden_estado_id', referencedColumnName: 'id', nullable: false)]
    private OrdenEstado $ordenEstado;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: 'usuario_id', referencedColumnName: 'id', nullable: false)]
    private Usuario $usuario;

    #[ORM\ManyToOne(targetEntity: Producto::class)]
    #[ORM\JoinColumn(name: 'producto_id', referencedColumnName: 'id', nullable: false)]
    private Producto $producto;

    #[ORM\Column(name: 'fecha_factura', type: 'date_immutable')]
    private \DateTimeImmutable $fechaFactura;

    #[ORM\Column(name: 'numero_factura', type: 'string', length: 255, nullable: true)]
    private ?string $numeroFactura = null;

    #[ORM\Column(name: 'descripcion_compra', type: 'string', length: 500, nullable: true)]
    private ?string $descripcionCompra = null;

    #[ORM\Column(name: 'reporte_cliente', type: 'string', length: 1000, nullable: true)]
    private ?string $reporteCliente = null;

    #[ORM\Column(name: 'orden_relaciona', type: 'string', length: 255, nullable: true)]
    private ?string $ordenRelaciona = null;

    /** 1, 2 o 3 — ver comentario original en la migración. */
    #[ORM\Column(name: 'tipo_ingreso', type: 'smallint', nullable: true)]
    private ?int $tipoIngreso = null;

    /** Se habilita después de creada la orden (ej. cuando el técnico la atiende) — no se pide en el formulario de creación, ver Version20261012192602. */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $diagnostico = null;

    /** Ídem `diagnostico`: se habilita después de creada la orden. */
    #[ORM\Column(name: 'reporte_final', type: 'string', length: 255, nullable: true)]
    private ?string $reporteFinal = null;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => 'HESR'])]
    private string $comprobante = 'HESR';

    #[ORM\Column(type: 'integer')]
    private int $numero;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    public function __construct(
        Cas $cas,
        CasCliente $casCliente,
        OrdenEstado $ordenEstado,
        Usuario $usuario,
        Producto $producto,
        \DateTimeImmutable $fechaFactura,
        int $numero,
    ) {
        $this->cas = $cas;
        $this->casCliente = $casCliente;
        $this->ordenEstado = $ordenEstado;
        $this->usuario = $usuario;
        $this->producto = $producto;
        $this->fechaFactura = $fechaFactura;
        $this->numero = $numero;
        $this->creadoEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCas(): Cas
    {
        return $this->cas;
    }

    public function getCasCliente(): CasCliente
    {
        return $this->casCliente;
    }

    public function getOrdenEstado(): OrdenEstado
    {
        return $this->ordenEstado;
    }

    public function setOrdenEstado(OrdenEstado $ordenEstado): static
    {
        $this->ordenEstado = $ordenEstado;

        return $this;
    }

    public function getUsuario(): Usuario
    {
        return $this->usuario;
    }

    public function getProducto(): Producto
    {
        return $this->producto;
    }

    public function setProducto(Producto $producto): static
    {
        $this->producto = $producto;

        return $this;
    }

    public function getFechaFactura(): \DateTimeImmutable
    {
        return $this->fechaFactura;
    }

    public function setFechaFactura(\DateTimeImmutable $fechaFactura): static
    {
        $this->fechaFactura = $fechaFactura;

        return $this;
    }

    public function getNumeroFactura(): ?string
    {
        return $this->numeroFactura;
    }

    public function setNumeroFactura(?string $numeroFactura): static
    {
        $this->numeroFactura = $numeroFactura;

        return $this;
    }

    public function getDescripcionCompra(): ?string
    {
        return $this->descripcionCompra;
    }

    public function setDescripcionCompra(?string $descripcionCompra): static
    {
        $this->descripcionCompra = $descripcionCompra;

        return $this;
    }

    public function getReporteCliente(): ?string
    {
        return $this->reporteCliente;
    }

    public function setReporteCliente(?string $reporteCliente): static
    {
        $this->reporteCliente = $reporteCliente;

        return $this;
    }

    public function getOrdenRelaciona(): ?string
    {
        return $this->ordenRelaciona;
    }

    public function setOrdenRelaciona(?string $ordenRelaciona): static
    {
        $this->ordenRelaciona = $ordenRelaciona;

        return $this;
    }

    public function getTipoIngreso(): ?int
    {
        return $this->tipoIngreso;
    }

    public function setTipoIngreso(?int $tipoIngreso): static
    {
        $this->tipoIngreso = $tipoIngreso;

        return $this;
    }

    public function getDiagnostico(): ?string
    {
        return $this->diagnostico;
    }

    public function setDiagnostico(?string $diagnostico): static
    {
        $this->diagnostico = $diagnostico;

        return $this;
    }

    public function getReporteFinal(): ?string
    {
        return $this->reporteFinal;
    }

    public function setReporteFinal(?string $reporteFinal): static
    {
        $this->reporteFinal = $reporteFinal;

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

    public function getComprobante(): string
    {
        return $this->comprobante;
    }

    public function setComprobante(string $comprobante): static
    {
        $this->comprobante = $comprobante;

        return $this;
    }

    public function getNumero(): int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }
}
