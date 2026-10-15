<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\RolePermiso;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RolePermiso>
 */
class RolePermisoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RolePermiso::class);
    }

    public function findOneByRole(string $role): ?RolePermiso
    {
        return $this->findOneBy(['role' => $role]);
    }

    /** @param int[] $ids @return array<int, string> id => role */
    public function nombresPorIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $rows = $this->getEntityManager()->getConnection()
            ->executeQuery(
                'SELECT id, role FROM gbl.role_permiso WHERE id IN (:ids)',
                ['ids' => $ids],
                ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
            )
            ->fetchAllAssociative();

        $mapa = [];
        foreach ($rows as $row) {
            $mapa[(int) $row['id']] = $row['role'];
        }

        return $mapa;
    }
}
