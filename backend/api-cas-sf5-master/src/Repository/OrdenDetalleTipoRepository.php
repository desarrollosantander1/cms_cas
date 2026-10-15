<?php

namespace App\Repository;

use App\Entity\OrdenDetalleTipo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrdenDetalleTipo>
 */
class OrdenDetalleTipoRepository extends ServiceEntityRepository
{   
    public const REPUESTO           = '1';
    public const REPUESTO_ADICIONAL = '2';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrdenDetalleTipo::class);
    }
}
