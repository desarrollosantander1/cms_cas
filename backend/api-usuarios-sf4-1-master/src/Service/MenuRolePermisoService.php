<?php

namespace App\Service;

use App\Entity\Menu;
use App\Entity\MenuRolePermiso;
use App\Entity\RolePermiso;
use App\Repository\MenuRolePermisoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Asociación menú <-> role_permiso: el nivel máximo permitido depende de si
 * el menú es de primer nivel (módulo) o un submenú (ver
 * MenuTreeService::arbolPodado()). Un menú padre representa "pertenencia a
 * un módulo", así que se mantiene acotado a primer/segundo nivel. Un
 * submenú apunta a una pantalla concreta (ej. "Gestion usuarios"), y ahí sí
 * tiene sentido exigir el permiso puntual de nivel 3 que esa pantalla
 * realmente usa (ej. ROLE_USUARIO_VER) — sin esto, solo quien tuviera el
 * rol de nivel 2 completo (que implica crear/editar/eliminar) podía ver el
 * submenú, aunque el endpoint detrás solo pida el permiso de nivel 3.
 */
class MenuRolePermisoService
{
    private const NIVEL_MAXIMO_MENU_PADRE = 2;
    private const NIVEL_MAXIMO_MENU_HIJO = 3;

    public function __construct(
        private readonly MenuRolePermisoRepository $menuRolesPermiso,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** @return RolePermiso[] */
    public function rolesDe(Menu $menu): array
    {
        return array_map(
            fn (MenuRolePermiso $mrp) => $mrp->getRolePermiso(),
            $this->menuRolesPermiso->asociadosActivos($menu),
        );
    }

    public function asociar(Menu $menu, RolePermiso $rol): void
    {
        $nivelMaximo = $menu->getMenuPadre() === null ? self::NIVEL_MAXIMO_MENU_PADRE : self::NIVEL_MAXIMO_MENU_HIJO;

        if ($rol->nivel() > $nivelMaximo) {
            throw new BadRequestHttpException(sprintf('Este menú solo admite roles hasta el nivel %d.', $nivelMaximo));
        }

        if ($this->menuRolesPermiso->buscarAsociacion($menu, $rol) === null) {
            $this->em->persist(new MenuRolePermiso($menu, $rol));
            $this->em->flush();
        }
    }

    public function desasociar(Menu $menu, RolePermiso $rol): void
    {
        $asociacion = $this->menuRolesPermiso->buscarAsociacion($menu, $rol);
        if ($asociacion !== null) {
            $this->em->remove($asociacion);
            $this->em->flush();
        }
    }
}
