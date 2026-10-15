<?php

namespace App\Repository;

use App\Entity\Usuario;
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

    public function findOneByEmail(string $email): ?Usuario
    {
        return $this->findOneBy(['email' => $email]);
    }
}
