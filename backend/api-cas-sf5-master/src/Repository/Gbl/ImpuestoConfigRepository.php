<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ImpuestoConfig;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImpuestoConfig>
 */
class ImpuestoConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImpuestoConfig::class);
    }
}
