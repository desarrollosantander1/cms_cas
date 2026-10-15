<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\Cliente;
use App\Entity\Gbl\Factura;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Factura>
 */
class FacturaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Factura::class);
    }

    public function findOneByNetsuiteId(string $netsuiteId): ?Factura
    {
        return $this->findOneBy(['netsuiteId' => $netsuiteId]);
    }

    /** Para el chequeo previo de la constraint UNIQUE(comprobante, numero) — ver IngestaFacturaService. */
    public function findOneByComprobanteYNumero(string $comprobante, string $numero): ?Factura
    {
        return $this->findOneBy(['comprobante' => $comprobante, 'numero' => $numero]);
    }

    /**
     * Factura no tiene FK propia a Cliente — se llega a él vía
     * clienteSucursal (obligatoria), de ahí el join.
     *
     * Solo forma de pago Crédito entra a datacrédito — no todas las
     * facturas que se sincronizan son a crédito (ver FormaPago). Se filtra
     * por el id del catálogo (FormaPagoRepository::ID_CREDITO), no por
     * nombre — el mismo criterio que ya usa CalculadorNovedad con los
     * códigos de Novedad.
     *
     * @return Factura[]
     */
    public function findByCliente(Cliente $cliente): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.clienteSucursal', 'cs')
            ->andWhere('cs.cliente = :cliente')
            ->andWhere('IDENTITY(f.formaPago) = :formaPagoCredito')
            ->setParameter('cliente', $cliente)
            ->setParameter('formaPagoCredito', FormaPagoRepository::ID_CREDITO)
            ->orderBy('f.fechaEmision', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Facturas con saldo pendiente de un NIT/cédula, para el Generador de
     * Cartas. Equivale al searchFacturasByNit() del legacy: solo saldo > 0,
     * a crédito (mismo filtro ID_CREDITO que findByCliente), ordenadas por
     * fecha de emisión ascendente. El cálculo de fecha de vencimiento / días
     * de mora / interés lo hace ArmadorCartaService, no esta consulta.
     *
     * @return Factura[]
     */
    public function buscarPorNitConSaldo(string $nit): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.clienteSucursal', 'cs')
            ->join('cs.cliente', 'c')
            ->andWhere('c.numeroIdentidad = :nit')
            ->andWhere('f.saldoActual > 0')
            ->andWhere('IDENTITY(f.formaPago) = :formaPagoCredito')
            ->setParameter('nit', $nit)
            ->setParameter('formaPagoCredito', FormaPagoRepository::ID_CREDITO)
            ->orderBy('f.fechaEmision', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
