<?php

namespace App\Repository;

use App\Entity\PeriodoCierre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PeriodoCierre>
 */
class PeriodoCierreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PeriodoCierre::class);
    }

    public function findOneByFechaReporte(\DateTimeImmutable $fechaReporte): ?PeriodoCierre
    {
        return $this->findOneBy(['fechaReporte' => $fechaReporte]);
    }

    /** @return PeriodoCierre[] más recientes primero */
    public function findAllOrdenado(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.fechaReporte', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** El período cerrado más reciente anterior a $fechaReporte — base para "saldo anterior". */
    public function findUltimoCerradoAntesDe(\DateTimeImmutable $fechaReporte): ?PeriodoCierre
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.abierto = false')
            ->andWhere('p.fechaReporte < :fecha')
            ->setParameter('fecha', $fechaReporte)
            ->orderBy('p.fechaReporte', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
