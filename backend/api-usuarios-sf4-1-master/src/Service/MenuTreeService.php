<?php

namespace App\Service;

use App\Entity\Menu;
use App\Repository\MenuGrupoRepository;
use App\Repository\MenuRepository;
use App\Repository\MenuRolePermisoRepository;

/**
 * Construye el árbol menu_grupo -> menu padre -> menu hijo (auto-referenciado
 * por menu_padre_id, limitado a dos niveles — ver MenuService::validarNivelMaximo())
 * desde las tablas planas gbl.menu_grupo / gbl.menu.
 */
class MenuTreeService
{
    public function __construct(
        private readonly MenuGrupoRepository $menuGrupos,
        private readonly MenuRepository $menus,
        private readonly MenuRolePermisoRepository $menuRolePermisos,
    ) {
    }

    /** Árbol completo, sin podar (pantalla de gestión): activos e inactivos, sin evaluar roles. */
    public function arbolCompleto(): array
    {
        return $this->construir(null);
    }

    /**
     * Árbol podado a los role_permiso efectivos del usuario (asignados +
     * todos sus descendientes — ver PermisosEfectivosService::idsParaUsuario()).
     * Regla de negocio (por menú, padre o hijo):
     *   1. Se valida primero el menú padre: si el usuario tiene alguno de
     *      los role_permiso asociados al padre, el padre es visible y
     *      TODOS sus hijos activos se muestran — se omite la validación
     *      propia de cada hijo.
     *   2. Si el usuario NO tiene ningún rol del padre, se evalúa cada
     *      hijo por separado: se muestra únicamente el/los hijo(s) cuyo
     *      propio role_permiso sí tenga el usuario. El padre igual se
     *      muestra (como contenedor) si sobrevive al menos un hijo así.
     *   3. Un menú (padre u hoja) sin ningún role_permiso asociado nunca
     *      es visible — no hay "sin restricción = visible para todos".
     *
     * @param int[] $rolePermisoIdsEfectivos
     */
    public function arbolPodado(array $rolePermisoIdsEfectivos): array
    {
        return $this->construir($rolePermisoIdsEfectivos);
    }

    /**
     * Ids de menú hijo "bonificados" por la regla de bypass padre→hijos:
     * únicamente los hijos de un padre cuyo PROPIO role_permiso el usuario
     * ya tiene (tienePadre === true en construirPadres()). Usado por
     * PermisosEfectivosService para conceder también el role_permiso propio
     * de esos hijos, aunque no cuelguen de la rama de roles del usuario
     * (ver ROLE_CARTERA vs ROLE_ADMIN).
     *
     * OJO: esto NO es lo mismo que "ids de menú visibles en el árbol". Un
     * padre puede aparecer en arbolPodado() como contenedor vacío solo
     * porque uno de sus hijos es visible de forma independiente por su
     * propio rol (ver regla 2 en el docblock de arbolPodado()) — eso es
     * puramente de despliegue del sidebar, no una concesión de permiso.
     * Tratar esa visibilidad de contenedor como "el usuario tiene el rol
     * del padre" sería una escalación de privilegios (un rol suelto bajo
     * "Aplicación" terminaría concediendo ROLE_ADMIN). Por eso este método
     * reimplementa el chequeo de `tienePadre` en vez de reusar la salida ya
     * serializada de arbolPodado() — reutiliza sí el mismo `tieneAlguno()`
     * privado que usa construirPadres(), así el criterio en sí no diverge.
     *
     * @param int[] $rolePermisoIdsEfectivos
     * @return int[]
     */
    public function idsMenuBonificadosPorPadre(array $rolePermisoIdsEfectivos): array
    {
        $mapaPermisos = $this->menuRolePermisos->mapaMenuRolePermiso();
        $todos = $this->menus->findAllOrdenado();

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
            if (!$this->tieneAlguno($rolesPadre, $rolePermisoIdsEfectivos)) {
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

    private function construir(?array $rolePermisoIdsEfectivos): array
    {
        $todos = $this->menus->findAllOrdenado();
        $mapaPermisos = $this->menuRolePermisos->mapaMenuRolePermiso();

        $porGrupo = [];
        $porPadre = [];
        foreach ($todos as $menu) {
            if ($menu->getMenuPadre() === null) {
                $porGrupo[$menu->getMenuGrupo()->getId()][] = $menu;
            } else {
                $porPadre[$menu->getMenuPadre()->getId()][] = $menu;
            }
        }

        // Se listan TODOS los grupos (no solo los que ya tienen algún menú)
        // y en su propio orden — un grupo recién creado, todavía vacío,
        // también debe verse en la pantalla de gestión.
        $grupos = [];
        foreach ($this->menuGrupos->findAllOrdenado() as $menuGrupo) {
            $grupoId = $menuGrupo->getId();
            $padres = $this->construirPadres($porPadre, $mapaPermisos, $porGrupo[$grupoId] ?? [], $rolePermisoIdsEfectivos);

            // Podando (usuario final): un grupo inactivo, o que se quedó sin
            // ningún menú visible, no se muestra. Sin podar (pantalla de
            // gestión) se listan todos, activos e inactivos, para poder
            // reactivarlos.
            if ($rolePermisoIdsEfectivos !== null && (!$menuGrupo->isEstado() || count($padres) === 0)) {
                continue;
            }

            $grupos[] = [
                'id' => $grupoId,
                'grupo' => $menuGrupo->getGrupo(),
                'icono' => $menuGrupo->getIcono(),
                'estado' => $menuGrupo->isEstado(),
                'orden' => $menuGrupo->getOrden(),
                'children' => $padres,
            ];
        }

        return $grupos;
    }

    /**
     * @param array<int, Menu[]> $porPadre
     * @param array<int, int[]> $mapaPermisos
     * @param Menu[] $padres menús de primer nivel del grupo
     */
    private function construirPadres(array $porPadre, array $mapaPermisos, array $padres, ?array $rolePermisoIdsEfectivos): array
    {
        $resultado = [];

        foreach ($padres as $padre) {
            $hijosOriginales = $porPadre[$padre->getId()] ?? [];

            if ($rolePermisoIdsEfectivos === null) {
                // Pantalla de gestión: se arma todo el árbol tal cual está, sin evaluar roles ni estado.
                $hijos = $this->construirHijos($mapaPermisos, $hijosOriginales, null, false);
                $resultado[] = $this->serializar($padre, $hijos);
                continue;
            }

            $rolesPadre = $mapaPermisos[$padre->getId()] ?? [];
            $tienePadre = $this->tieneAlguno($rolesPadre, $rolePermisoIdsEfectivos);
            $hijos = $this->construirHijos($mapaPermisos, $hijosOriginales, $rolePermisoIdsEfectivos, $tienePadre);

            // Con hijos: el padre se muestra si el usuario tiene su propio
            // rol (entran todos los hijos) o si, sin tenerlo, igual quedó
            // al menos un hijo visible por su propio rol. Sin hijos (hoja
            // de primer nivel): solo cuenta su propio rol.
            $visible = $padre->isEstado() && (count($hijosOriginales) > 0 ? count($hijos) > 0 : $tienePadre);

            if (!$visible) {
                continue;
            }

            $resultado[] = $this->serializar($padre, $hijos);
        }

        return $resultado;
    }

    /**
     * @param array<int, int[]> $mapaPermisos
     * @param Menu[] $hijosOriginales
     */
    private function construirHijos(array $mapaPermisos, array $hijosOriginales, ?array $rolePermisoIdsEfectivos, bool $tienePadre): array
    {
        $resultado = [];

        foreach ($hijosOriginales as $hijo) {
            if ($rolePermisoIdsEfectivos !== null) {
                // El padre ya habilitó a todos sus hijos: se omite la
                // validación propia de cada uno. Si no, cada hijo depende
                // únicamente de su propio role_permiso.
                $rolesHijo = $mapaPermisos[$hijo->getId()] ?? [];
                $visible = $hijo->isEstado() && ($tienePadre || $this->tieneAlguno($rolesHijo, $rolePermisoIdsEfectivos));

                if (!$visible) {
                    continue;
                }
            }

            // Los menús hijos, por regla de negocio, no tienen submenús propios.
            $resultado[] = $this->serializar($hijo, []);
        }

        return $resultado;
    }

    /** @param int[] $requeridos @param int[] $idsUsuario */
    private function tieneAlguno(array $requeridos, array $idsUsuario): bool
    {
        return count($requeridos) > 0 && count(array_intersect($requeridos, $idsUsuario)) > 0;
    }

    private function serializar(Menu $menu, array $hijos): array
    {
        return [
            'id' => $menu->getId(),
            'etiqueta' => $menu->getMenu(),
            'icono' => $menu->getIcono(),
            'ruta' => $menu->getRuta(),
            'estado' => $menu->isEstado(),
            'orden' => $menu->getOrden(),
            'menuGrupoId' => $menu->getMenuGrupo()->getId(),
            'menuPadreId' => $menu->getMenuPadre()?->getId(),
            'children' => $hijos,
        ];
    }
}
