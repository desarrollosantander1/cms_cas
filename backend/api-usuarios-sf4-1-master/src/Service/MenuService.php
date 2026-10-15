<?php

namespace App\Service;

use App\Entity\Menu;
use App\Entity\MenuGrupo;
use App\Repository\MenuGrupoRepository;
use App\Repository\MenuRepository;
use App\Repository\RolePermisoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Reglas de negocio de menu: un hijo siempre hereda el menu_grupo de su
 * padre (no tiene sentido que un submenú "pertenezca" a otro grupo distinto
 * del de su padre), un nodo no puede quedar como su propio ancestro, la
 * jerarquía se limita a dos niveles (grupo -> menú -> submenú, sin
 * submenús de submenús), la etiqueta no se repite entre hermanos, y todo
 * menú de primer nivel se asocia automáticamente a ROLE_ADMIN al crearse
 * (ver crear()). El controller solo traduce HTTP <-> este servicio.
 */
class MenuService
{
    private const ROLE_ADMINISTRADOR = 'ROLE_ADMIN';

    public function __construct(
        private readonly MenuRepository $menus,
        private readonly MenuGrupoRepository $menuGrupos,
        private readonly RolePermisoRepository $rolesPermiso,
        private readonly MenuRolePermisoService $menuRolePermisoService,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function buscar(int $id): Menu
    {
        $menu = $this->menus->find($id);
        if ($menu === null) {
            throw new NotFoundHttpException('Menú no encontrado.');
        }

        return $menu;
    }

    public function crear(array $datos): Menu
    {
        $etiqueta = trim((string) ($datos['menu'] ?? ''));
        if ($etiqueta === '') {
            throw new BadRequestHttpException('El campo "menu" es requerido.');
        }

        $padre = $this->resolverPadre($datos['menuPadreId'] ?? null);
        $this->validarNivelMaximo($padre);
        $grupo = $this->resolverGrupo($datos['menuGrupoId'] ?? null, $padre);

        if ($this->menus->existeHermano($etiqueta, $padre, $grupo)) {
            throw new ConflictHttpException('Ya existe un menú con esa etiqueta en este mismo nivel.');
        }

        $menu = new Menu();
        $menu->setMenu($etiqueta);
        $menu->setMenuGrupo($grupo);
        $menu->setMenuPadre($padre);
        $menu->setIcono($this->campoTextoOpcional($datos, 'icono'));
        $menu->setRuta($this->campoTextoOpcional($datos, 'ruta'));
        $menu->setOrden(isset($datos['orden']) ? (int) $datos['orden'] : $this->menus->proximoOrden($padre, $grupo));

        $this->em->persist($menu);
        $this->em->flush();

        if ($padre === null) {
            $this->asociarRoleAdmin($menu);
        }

        return $menu;
    }

    public function actualizar(Menu $menu, array $datos): Menu
    {
        $etiqueta = isset($datos['menu']) ? trim((string) $datos['menu']) : $menu->getMenu();
        if ($etiqueta === '') {
            throw new BadRequestHttpException('El campo "menu" es requerido.');
        }

        $padre = array_key_exists('menuPadreId', $datos)
            ? $this->resolverPadre($datos['menuPadreId'])
            : $menu->getMenuPadre();
        $this->validarSinCiclo($menu, $padre);
        $this->validarNivelMaximo($padre, $menu);

        $grupo = $padre !== null
            ? $padre->getMenuGrupo()
            : $this->resolverGrupo($datos['menuGrupoId'] ?? null, null, $menu->getMenuGrupo());

        if ($this->menus->existeHermano($etiqueta, $padre, $grupo, $menu)) {
            throw new ConflictHttpException('Ya existe un menú con esa etiqueta en este mismo nivel.');
        }

        if (isset($datos['orden'])) {
            $menu->setOrden((int) $datos['orden']);
        } elseif ($grupo !== $menu->getMenuGrupo() || $padre !== $menu->getMenuPadre()) {
            // Cambió de "hermanos" (grupo o padre) sin indicar orden explícito:
            // se manda al final del nuevo grupo, en vez de arrastrar una
            // posición que ya no tiene relación con sus nuevos hermanos.
            $menu->setOrden($this->menus->proximoOrden($padre, $grupo));
        }

        $menu->setMenu($etiqueta);
        $menu->setMenuGrupo($grupo);
        $menu->setMenuPadre($padre);
        if (array_key_exists('icono', $datos)) {
            $menu->setIcono($this->campoTextoOpcional($datos, 'icono'));
        }
        if (array_key_exists('ruta', $datos)) {
            $menu->setRuta($this->campoTextoOpcional($datos, 'ruta'));
        }

        $this->em->flush();

        return $menu;
    }

    public function activar(Menu $menu): void
    {
        $menu->setEstado(true);
        $this->em->flush();
    }

    public function desactivar(Menu $menu): void
    {
        $menu->setEstado(false);
        $this->em->flush();
    }

    /**
     * Borrado físico: elimina el menú y, en cascada a nivel de base de
     * datos (menu.menu_padre_id ON DELETE CASCADE ->
     * menu_role_permiso.menu_id ON DELETE CASCADE), sus submenús propios
     * (si es un menú padre) y las relaciones de rol asociadas a él y a
     * esos submenús. Operación irreversible — el controller exige
     * confirmación explícita del usuario antes de llegar acá.
     */
    public function eliminar(Menu $menu): void
    {
        $this->em->remove($menu);
        $this->em->flush();
    }

    private function resolverPadre(mixed $menuPadreId): ?Menu
    {
        if ($menuPadreId === null || $menuPadreId === '') {
            return null;
        }

        $padre = $this->menus->find((int) $menuPadreId);
        if ($padre === null) {
            throw new NotFoundHttpException('El menú padre indicado no existe.');
        }

        return $padre;
    }

    /** Sin padre, el grupo es obligatorio (propio o heredado en la actualización). */
    private function resolverGrupo(mixed $menuGrupoId, ?Menu $padre, ?MenuGrupo $actual = null): MenuGrupo
    {
        if ($padre !== null) {
            return $padre->getMenuGrupo();
        }

        if ($menuGrupoId === null || $menuGrupoId === '') {
            if ($actual !== null) {
                return $actual;
            }
            throw new BadRequestHttpException('El campo "menuGrupoId" es requerido para un menú de primer nivel.');
        }

        $grupo = $this->menuGrupos->find((int) $menuGrupoId);
        if ($grupo === null) {
            throw new NotFoundHttpException('El grupo de menú indicado no existe.');
        }

        return $grupo;
    }

    /**
     * La jerarquía se limita a dos niveles: un menú de segundo nivel (su
     * padre ya tiene padre) no puede recibir submenús propios, y un menú
     * que ya tiene submenús propios no puede convertirse en submenú de otro
     * (dejaría a sus hijos en un tercer nivel).
     */
    private function validarNivelMaximo(?Menu $padre, ?Menu $menu = null): void
    {
        if ($padre !== null && $padre->getMenuPadre() !== null) {
            throw new BadRequestHttpException('No se pueden crear submenús de un menú que ya es de segundo nivel.');
        }

        if ($padre !== null && $menu !== null && $menu->getHijos()->count() > 0) {
            throw new BadRequestHttpException('Este menú ya tiene submenús propios; no puede convertirse en submenú de otro.');
        }
    }

    /**
     * Todo menú de primer nivel queda asociado a ROLE_ADMIN desde que se
     * crea, para que el árbol de menú vaya creciendo sin que el administrador
     * tenga que ir asociando manualmente cada módulo nuevo — a medida que se
     * agregan menús, ROLE_ADMIN (y quien lo tenga, directo o heredado)
     * automáticamente sigue viendo todo. Los submenús no necesitan esta
     * asociación propia: heredan la del padre (ver MenuTreeService). Si el
     * entorno no tiene sembrado ROLE_ADMIN todavía, no bloquea la creación
     * del menú — simplemente queda sin esa asociación automática.
     */
    private function asociarRoleAdmin(Menu $menu): void
    {
        $admin = $this->rolesPermiso->findOneByRole(self::ROLE_ADMINISTRADOR);
        if ($admin !== null) {
            $this->menuRolePermisoService->asociar($menu, $admin);
        }
    }

    /** Un menú no puede terminar teniendo como padre a uno de sus propios descendientes (ni a sí mismo). */
    private function validarSinCiclo(Menu $menu, ?Menu $nuevoPadre): void
    {
        $candidato = $nuevoPadre;
        while ($candidato !== null) {
            if ($candidato === $menu) {
                throw new BadRequestHttpException('Un menú no puede ser su propio ancestro.');
            }
            $candidato = $candidato->getMenuPadre();
        }
    }

    private function campoTextoOpcional(array $datos, string $campo): ?string
    {
        $valor = trim((string) ($datos[$campo] ?? ''));

        return $valor === '' ? null : $valor;
    }
}
