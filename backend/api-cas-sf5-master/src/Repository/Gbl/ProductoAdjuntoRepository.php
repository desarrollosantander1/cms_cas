<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ProductoAdjunto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductoAdjunto>
 */
class ProductoAdjuntoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductoAdjunto::class);
    }
}
