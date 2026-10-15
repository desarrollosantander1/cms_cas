<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\ClienteTipo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClienteTipo>
 */
class ClienteTipoRepository extends ServiceEntityRepository
{
    public const NACIONAL = 'NACIONAL';
    public const EXTRANJERO = 'EXTRANJERO';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClienteTipo::class);
    }

    public function findOneByDescripcion(string $descripcion): ?ClienteTipo
    {
        return $this->findOneBy(['descripcion' => $descripcion]);
    }
}
