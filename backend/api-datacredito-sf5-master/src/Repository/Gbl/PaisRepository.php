<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\Pais;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Pais>
 */
class PaisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pais::class);
    }

    public function findOneByNetsuiteId(string $netsuiteId): ?Pais
    {
        return $this->findOneBy(['netsuiteId' => $netsuiteId]);
    }
}
