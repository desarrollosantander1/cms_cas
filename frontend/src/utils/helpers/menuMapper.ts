import type { MenuGrupoNode, MenuNode } from '@/services/usersService';
import type { menu } from '@/layouts/full/vertical-sidebar/sidebarItem';
import { PointIcon } from 'vue-tabler-icons';

const ICONO_GRUPO_POR_DEFECTO = 'mdi-folder-outline';

/**
 * Convierte el árbol backend (menu_grupo -> menu auto-referenciado, ya
 * podado por rol, parte de la respuesta de /me) a la interfaz `menu[]` que consume el sidebar
 * de la plantilla, con tres niveles: el grupo es el menú desplegable, sus
 * hijos directos son subtítulos no clicables (etiquetas) y los nietos son
 * los módulos navegables agrupados bajo cada etiqueta.
 *
 * El ícono del grupo sale de menu_grupo.icono tal como se guarda en base de
 * datos: un nombre de ícono MDI (ej. "mdi-account-group"), el mismo formato
 * que ya usa <v-icon> en el resto de la app — ver Icon.vue.
 */
export function mapBackendMenuToSidebar(grupos: MenuGrupoNode[]): menu[] {
    return grupos
        .filter((grupo) => grupo.children.length > 0)
        .map((grupo) => ({
            title: grupo.grupo,
            icon: grupo.icono || ICONO_GRUPO_POR_DEFECTO,
            children: grupo.children.flatMap((etiqueta) => [
                { header: etiqueta.etiqueta },
                ...etiqueta.children.map(mapModulo),
            ]),
        }));
}

function mapModulo(nodo: MenuNode): menu {
    const rutasUnicas = [...new Set(nodo.children.map((c) => c.ruta).filter(Boolean))];

    if (rutasUnicas.length <= 1) {
        return {
            title: nodo.etiqueta,
            icon: PointIcon,
            to: rutasUnicas[0] ?? nodo.ruta ?? undefined,
        };
    }

    return {
        title: nodo.etiqueta,
        icon: PointIcon,
        children: nodo.children.map((hijo) => ({
            title: hijo.etiqueta,
            icon: PointIcon,
            to: hijo.ruta ?? undefined,
        })),
    };
}
