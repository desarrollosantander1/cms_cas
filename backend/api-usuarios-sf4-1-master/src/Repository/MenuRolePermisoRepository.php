<?php

namespace App\Repository;

use App\Entity\Menu;
use App\Entity\MenuRolePermiso;
use App\Entity\RolePermiso;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MenuRolePermiso>
 */
class MenuRolePermisoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MenuRolePermiso::class);
    }

    /** @return MenuRolePermiso[] */
    public function asociadosActivos(Menu $menu): array
    {
        return $this->findBy(['menu' => $menu, 'estado' => true]);
    }

    public function buscarAsociacion(Menu $menu, RolePermiso $rol): ?MenuRolePermiso
    {
        return $this->findOneBy(['menu' => $menu, 'rolePermiso' => $rol]);
    }

    /**
     * role_permiso_id requeridos por cada menu_id (estado=true).
     *
     * @return array<int, int[]> menu_id => [role_permiso_id, ...]
     */
    public function mapaMenuRolePermiso(): array
    {
        $rows = $this->getEntityManager()->getConnection()
            ->executeQuery('SELECT menu_id, role_permiso_id FROM gbl.menu_role_permiso WHERE estado = true')
            ->fetchAllAssociative();

        $mapa = [];
        foreach ($rows as $row) {
            $mapa[(int) $row['menu_id']][] = (int) $row['role_permiso_id'];
        }

        return $mapa;
    }
}
