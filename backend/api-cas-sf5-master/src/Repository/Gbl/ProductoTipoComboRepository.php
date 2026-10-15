<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ProductoTipoCombo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductoTipoCombo>
 */
class ProductoTipoComboRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductoTipoCombo::class);
    }
}
