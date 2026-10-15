<?php

namespace App\Service;

use App\Entity\Gbl\Usuario;
use App\Repository\Gbl\MenuRepository;
use App\Repository\Gbl\RolePermisoRepository;
use App\Repository\Gbl\UsuarioRolePermisoRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Igual que en `users sf4-1`: unión de los role_permiso asignados a un
 * usuario más:
 *  1. todos sus descendientes en la jerarquía (role_padre_id) — ver
 *     UsuarioRolePermisoRepository::findEfectivos().
 *  2. los role_permiso propios de los hijos de cualquier menú padre cuyo
 *     rol el usuario ya tenga (ver idsMenuBonificadosPorPadre() más abajo).
 *
 * Se replica aquí (no se llama a `users` por HTTP) porque `ROLE_DATACREDITO_VER`
 * cuelga de `ROLE_CARTERA` — un árbol de roles independiente, no descendiente
 * de `ROLE_ADMIN` — y solo llega a un admin vía esta regla de bono. Sin
 * esto, un admin ve "Datacrédito" en el menú (lo decide `users`, con su
 * propia copia de esta misma regla) pero este servicio le respondería 403
 * en cada endpoint — el mismo bug que ya se corrigió una vez en `users`,
 * reaparecido aquí porque es una segunda copia del mismo mecanismo.
 */
class PermisosEfectivosService
{
    /** @var array<int, array<int, string>> usuarioId => [role_permiso_id => role] */
    private array $cache = [];

    public function __construct(
        private readonly UsuarioRolePermisoRepository $usuarioRolePermisos,
        private readonly RolePermisoRepository $rolePermisos,
        private readonly MenuRepository $menus,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return array<int, string> role_permiso_id => role */
    private function efectivos(Usuario $usuario): array
    {
        return $this->cache[$usuario->getId()] ??= $this->conRolesBonificadosPorMenu(
            $this->usuarioRolePermisos->findEfectivos($usuario)
        );
    }

    /**
     * @param array<int, string> $efectivos role_permiso_id => role, ya resueltos por jerarquía
     * @return array<int, string>
     */
    private function conRolesBonificadosPorMenu(array $efectivos): array
    {
        $menusBonificados = $this->idsMenuBonificadosPorPadre(array_keys($efectivos));
        $mapaMenuPermisos = $this->mapaMenuRolePermiso();

        $idsFaltantes = [];
        foreach ($menusBonificados as $menuId) {
            foreach ($mapaMenuPermisos[$menuId] ?? [] as $rolePermisoId) {
                if (!isset($efectivos[$rolePermisoId])) {
                    $idsFaltantes[$rolePermisoId] = true;
                }
            }
        }

        if ($idsFaltantes === []) {
            return $efectivos;
        }

        return $efectivos + $this->rolePermisos->nombresPorIds(array_keys($idsFaltantes));
    }

    /**
     * Ids de menú hijo "bonificados": solo los hijos de un padre cuyo
     * PROPIO role_permiso el usuario ya tiene — nunca "qué se ve en el
     * árbol podado" (un padre puede aparecer como contenedor vacío por un
     * hijo visible de forma independiente; tratar eso como "el usuario
     * tiene el rol del padre" sería una escalación de privilegios). Puerto
     * fiel de MenuTreeService::idsMenuBonificadosPorPadre() en `users sf4-1`.
     *
     * @param int[] $rolePermisoIdsEfectivos
     * @return int[]
     */
    private function idsMenuBonificadosPorPadre(array $rolePermisoIdsEfectivos): array
    {
        $mapaPermisos = $this->mapaMenuRolePermiso();
        $todos = $this->menus->findAll();

        $porPadre = [];
        foreach ($todos as $menu) {
            if ($menu->getMenuPadre() !== null) {
                $porPadre[$menu->getMenuPadre()->getId()][] = $menu;
            }
        }

        $ids = [];
        foreach ($todos as $padre) {
            if ($padre->getMenuPadre() !== null || !$padre->isEstado()) {
                continue;
            }

            $rolesPadre = $mapaPermisos[$padre->getId()] ?? [];
            if (count($rolesPadre) === 0 || count(array_intersect($rolesPadre, $rolePermisoIdsEfectivos)) === 0) {
                continue;
            }

            foreach ($porPadre[$padre->getId()] ?? [] as $hijo) {
                if ($hijo->isEstado()) {
                    $ids[] = $hijo->getId();
                }
            }
        }

        return $ids;
    }

    /** @return array<int, int[]> menu_id => [role_permiso_id, ...] */
    private function mapaMenuRolePermiso(): array
    {
        $rows = $this->em->getConnection()
            ->executeQuery('SELECT menu_id, role_permiso_id FROM gbl.menu_role_permiso WHERE estado = true')
            ->fetchAllAssociative();

        $mapa = [];
        foreach ($rows as $row) {
            $mapa[(int) $row['menu_id']][] = (int) $row['role_permiso_id'];
        }

        return $mapa;
    }

    public function tiene(Usuario $usuario, string $role): bool
    {
        return in_array($role, $this->efectivos($usuario), true);
    }
}
