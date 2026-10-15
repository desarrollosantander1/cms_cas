<?php

namespace App\Repository;

use App\Entity\Novedad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Novedad>
 */
class NovedadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Novedad::class);
    }

    public function findOneByCodigo(int $codigo): ?Novedad
    {
        return $this->findOneBy(['codigo' => $codigo]);
    }

    /** @return Novedad[] todo el catálogo, ordenado por código */
    public function findAllOrdenado(): array
    {
        return $this->createQueryBuilder('n')
            ->orderBy('n.codigo', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
