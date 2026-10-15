<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\PaisDpto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaisDpto>
 */
class PaisDptoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaisDpto::class);
    }

    public function findOneByNetsuiteId(string $netsuiteId): ?PaisDpto
    {
        return $this->findOneBy(['netsuiteId' => $netsuiteId]);
    }
}
