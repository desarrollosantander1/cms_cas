<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\Usuario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Usuario>
 */
class UsuarioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Usuario::class);
    }

    public function findOneByUsuario(string $usuario): ?Usuario
    {
        return $this->findOneBy(['usuario' => $usuario]);
    }
}
