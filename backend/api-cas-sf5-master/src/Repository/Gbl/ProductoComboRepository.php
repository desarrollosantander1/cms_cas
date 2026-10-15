<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ProductoCombo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductoCombo>
 */
class ProductoComboRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductoCombo::class);
    }
}
