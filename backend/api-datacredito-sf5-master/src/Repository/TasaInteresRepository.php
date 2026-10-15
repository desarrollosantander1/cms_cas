<?php

namespace App\Repository;

use App\Entity\TasaInteres;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TasaInteres>
 */
class TasaInteresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TasaInteres::class);
    }

    /** La tasa vigente = la fila más reciente (id como desempate). */
    public function vigente(): ?TasaInteres
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.creadoEn', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
