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

    public function findOneByDocumento(string $documento): ?Usuario
    {
        return $this->findOneBy(['documento' => $documento]);
    }

    /** @return Usuario[] activos, sin incluir $excluirId, ordenados por nombre/apellido */
    public function findActivosExcluyendo(int $excluirId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.estado = true')
            ->andWhere('u.id != :excluirId')
            ->setParameter('excluirId', $excluirId)
            ->orderBy('u.nombre', 'ASC')
            ->addOrderBy('u.apellido', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
