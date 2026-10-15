<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ClienteTipoIdentificacion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClienteTipoIdentificacion>
 */
class ClienteTipoIdentificacionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClienteTipoIdentificacion::class);
    }

    public function findOneByCodigo(string $codigo): ?ClienteTipoIdentificacion
    {
        return $this->findOneBy(['codigo' => $codigo]);
    }
}
