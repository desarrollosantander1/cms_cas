<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\PaisDptoCiudad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaisDptoCiudad>
 */
class PaisDptoCiudadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaisDptoCiudad::class);
    }

    public function findOneByNetsuiteId(string $netsuiteId): ?PaisDptoCiudad
    {
        return $this->findOneBy(['netsuiteId' => $netsuiteId]);
    }
}
