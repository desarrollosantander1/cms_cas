<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ProductoComboDetalle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductoComboDetalle>
 */
class ProductoComboDetalleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductoComboDetalle::class);
    }
}
