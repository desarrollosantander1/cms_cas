<?php

namespace App\Repository;

use App\Entity\OrdenEstadoLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrdenEstadoLog>
 */
class OrdenEstadoLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrdenEstadoLog::class);
    }
}
