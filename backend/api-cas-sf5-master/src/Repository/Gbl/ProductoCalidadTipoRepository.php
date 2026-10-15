<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ProductoCalidadTipo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProductoCalidadTipo>
 */
class ProductoCalidadTipoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductoCalidadTipo::class);
    }
}
