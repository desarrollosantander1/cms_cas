<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\Empleado;
use App\Entity\Gbl\Usuario;
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
