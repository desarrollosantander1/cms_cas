<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\LineaSublinea;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LineaSublinea>
 */
class LineaSublineaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LineaSublinea::class);
    }
}
