<?php

namespace App\Repository;

use App\Entity\Empleado;
use App\Entity\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Empleado>
 */
class EmpleadoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Empleado::class);
    }

    public function findOneByUsuario(Usuario $usuario): ?Empleado
    {
        return $this->findOneBy(['usuario' => $usuario]);
    }
}
