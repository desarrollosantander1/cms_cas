<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ClienteSucursal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClienteSucursal>
 */
class ClienteSucursalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClienteSucursal::class);
    }

    public function findOneByNetsuiteId(string $netsuiteId): ?ClienteSucursal
    {
        return $this->findOneBy(['netsuiteId' => $netsuiteId]);
    }
}
