<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ProductoImpuesto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductoImpuesto>
 */
class ProductoImpuestoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductoImpuesto::class);
    }
}
