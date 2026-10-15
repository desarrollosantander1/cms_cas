<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ProductoRepuesto;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductoRepuesto>
 */
class ProductoRepuestoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductoRepuesto::class);
    }
}
