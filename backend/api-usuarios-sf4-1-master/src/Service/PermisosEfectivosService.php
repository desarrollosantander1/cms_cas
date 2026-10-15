<?php

namespace App\Service;

use App\Entity\Usuario;
use App\Repository\MenuRolePermisoRepository;
use App\Repository\RolePermisoRepository;
use App\Repository\UsuarioRolePermisoRepository;

/**
 * Unión de los role_permiso asignados a un usuario más:
 *  1. todos sus descendientes en la jerarquía (role_padre_id) — asignar un
 *     nodo padre concede automáticamente todo lo que cuelga debajo. Ver
 *     UsuarioRolePermisoRepository::findEfectivos().
 *  2. los role_permiso propios de los hijos de cualquier menú padre cuyo
 *     rol el usuario ya tenga — ver
 *     MenuTreeService::idsMenuBonificadosPorPadre() (misma regla de bypass
 *     padre→hijos que arbolPodado() usa para el sidebar; deliberadamente
 *     NO se basa en "qué aparece en el árbol", ver el porqué en ese
 *     método). Sin esto, un rol como ROLE_CARTERA —árbol de roles
 *     independiente, no descendiente de ROLE_ADMIN— deja al usuario ver el
 *     módulo en el menú pero sus llamadas a la API igual dan 403, porque el
 *     (1) por sí solo no lo cubre.
 */
class PermisosEfectivosService
{
    /** @var array<int, array<int, string>> usuarioId => [role_permiso_id => role] */
    private array $cache = [];

    public function __construct(
        private readonly UsuarioRolePermisoRepository $usuarioRolePermisos,
        private readonly MenuTreeService $menuTree,
        private readonly MenuRolePermisoRepository $menuRolePermisos,
        private readonly RolePermisoRepository $rolePermisos,
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
        $menusBonificados = $this->menuTree->idsMenuBonificadosPorPadre(array_keys($efectivos));
        $mapaMenuPermisos = $this->menuRolePermisos->mapaMenuRolePermiso();

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

    /** @return string[] códigos "role" efectivos */
    public function paraUsuario(Usuario $usuario): array
    {
        return array_values($this->efectivos($usuario));
    }

    /** @return int[] ids de role_permiso efectivos (asignados + descendientes + vía menú visible, ver conRolesBonificadosPorMenu()), para autorización y para MenuTreeService::arbolPodado() */
    public function idsParaUsuario(Usuario $usuario): array
    {
        return array_keys($this->efectivos($usuario));
    }

    public function tiene(Usuario $usuario, string $role): bool
    {
        return in_array($role, $this->efectivos($usuario), true);
    }

    /** @param int[] $rolePermisoIds cualquiera de estos ids alcanza */
    public function tieneAlgunId(Usuario $usuario, array $rolePermisoIds): bool
    {
        $efectivosIds = array_keys($this->efectivos($usuario));

        return count(array_intersect($rolePermisoIds, $efectivosIds)) > 0;
    }
}
