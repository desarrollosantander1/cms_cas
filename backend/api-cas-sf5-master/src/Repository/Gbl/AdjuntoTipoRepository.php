<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\AdjuntoTipo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdjuntoTipo>
 */
class AdjuntoTipoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdjuntoTipo::class);
    }
}
