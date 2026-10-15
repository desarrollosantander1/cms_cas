<?php

namespace App\Repository\Gbl;

use App\Entity\Gbl\RolePermiso;
use App\Entity\Gbl\Usuario;
use App\Entity\Gbl\UsuarioRolePermiso;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UsuarioRolePermiso>
 */
class UsuarioRolePermisoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UsuarioRolePermiso::class);
    }

    public function buscarAsignacion(Usuario $usuario, RolePermiso $rol): ?UsuarioRolePermiso
    {
        return $this->findOneBy(['usuario' => $usuario, 'rolePermiso' => $rol]);
    }

    /**
     * "Efectivos" = los role_permiso asignados directamente al usuario, más
     * todos sus descendientes en la jerarquía (role_padre_id). Asignar un
     * nodo padre (ej. ROLE_USUARIO) da automáticamente todo lo que cuelga
     * debajo (ROLE_USUARIO_CREAR, ROLE_USUARIO_EDITAR, ...) sin asignarlos
     * uno por uno. Esta misma consulta resuelve tanto la autorización de
     * endpoints (PermisoVoter) como la poda del menú (MenuTreeService).
     *
     * @return array<int, string> id => role, de todos los nodos efectivos
     */
    public function findEfectivos(Usuario $usuario): array
    {
        $sql = <<<SQL
            WITH RECURSIVE asignados AS (
                SELECT rp.id, rp.role
                FROM gbl.role_permiso rp
                JOIN gbl.usuario_role_permiso urp ON urp.role_permiso_id = rp.id
                WHERE urp.usuario_id = :usuarioId AND urp.estado = true AND rp.estado = true
            ),
            efectivos AS (
                SELECT id, role FROM asignados
                UNION ALL
                SELECT rp.id, rp.role
                FROM gbl.role_permiso rp
                JOIN efectivos e ON rp.role_padre_id = e.id
                WHERE rp.estado = true
            )
            SELECT DISTINCT id, role FROM efectivos
            SQL;

        $rows = $this->getEntityManager()->getConnection()
            ->executeQuery($sql, ['usuarioId' => $usuario->getId()])
            ->fetchAllAssociative();

        $efectivos = [];
        foreach ($rows as $row) {
            $efectivos[(int) $row['id']] = $row['role'];
        }

        return $efectivos;
    }
}
