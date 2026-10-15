<?php

namespace App\Repository;

use App\Entity\Gbl\Factura;
use App\Entity\PeriodoCierre;
use App\Entity\ReporteFactura;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReporteFactura>
 */
class ReporteFacturaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReporteFactura::class);
    }

    public function findOneByPeriodoYFactura(PeriodoCierre $periodo, Factura $factura): ?ReporteFactura
    {
        return $this->findOneBy(['periodoCierre' => $periodo, 'factura' => $factura]);
    }

    /** @return array<int, ReporteFactura> indexado por factura_id, para resolver un período completo sin N+1 */
    public function mapaPorFacturaId(PeriodoCierre $periodo): array
    {
        $filas = $this->findBy(['periodoCierre' => $periodo]);

        $mapa = [];
        foreach ($filas as $fila) {
            $mapa[$fila->getFactura()->getId()] = $fila;
        }

        return $mapa;
    }

    /**
     * La fotografía de esta factura en el período CERRADO más reciente
     * anterior a $antesDe — base real de "saldo anterior" (el legacy
     * llamaba a esto SALDOS_REPORTE_ANTERIOR). Null si esta factura nunca
     * se reportó en un período ya cerrado (primera vez que se reporta).
     */
    public function findUltimoCerradoParaFactura(Factura $factura, \DateTimeImmutable $antesDe): ?ReporteFactura
    {
        return $this->createQueryBuilder('rf')
            ->join('rf.periodoCierre', 'p')
            ->andWhere('rf.factura = :factura')
            ->andWhere('p.abierto = false')
            ->andWhere('p.fechaReporte < :antesDe')
            ->setParameter('factura', $factura)
            ->setParameter('antesDe', $antesDe)
            ->orderBy('p.fechaReporte', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
