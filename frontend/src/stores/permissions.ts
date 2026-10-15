import { defineStore } from 'pinia';
import { usersService, type MenuGrupoNode, type MenuNode } from '@/services/usersService';

/** Recorre children recursivamente buscando un nodo cuya ruta coincida — misma poda que ya aplicó MenuTreeService::arbolPodado() en el backend (regla "padre visible => todos los hijos visibles"). */
function rutaEnArbol(nodos: (MenuGrupoNode | MenuNode)[], ruta: string): boolean {
    return nodos.some((nodo) => ('ruta' in nodo && nodo.ruta === ruta) || rutaEnArbol(nodo.children, ruta));
}

/**
 * Carga en curso, compartida entre quien llame a cargar() en paralelo — al
 * refrescar la página, el bootstrap async de main.ts y el guard de
 * router/index.ts pueden disparar la carga casi al mismo tiempo (el router
 * resuelve su navegación inicial apenas se instala, sin esperar al
 * bootstrap). Sin esto, /me se pediría dos veces.
 */
let cargaEnCurso: Promise<void> | null = null;

export const usePermissionsStore = defineStore('permissions', {
    state: () => ({
        roles: [] as string[],
        menu: [] as MenuGrupoNode[],
        loaded: false,
    }),
    actions: {
        /** Se llama tras login/rehidratación; si ya está cargado o hay una carga en curso, la reutiliza en vez de duplicar la petición. */
        async cargar() {
            if (this.loaded) return;
            if (cargaEnCurso) return cargaEnCurso;

            cargaEnCurso = (async () => {
                const { roles, menu } = await usersService.miSesion();
                this.roles = roles;
                this.menu = menu;
                this.loaded = true;
            })().finally(() => {
                cargaEnCurso = null;
            });

            return cargaEnCurso;
        },
        limpiar() {
            this.roles = [];
            this.menu = [];
            this.loaded = false;
            cargaEnCurso = null;
        },
        can(role: string): boolean {
            return this.roles.includes(role);
        },
        /**
         * Acceso a una vista principal (guard del router): además del rol
         * estricto (jerarquía role_padre_id, ver can()), acepta que la ruta
         * ya sea visible en el árbol de menú podado — mismo criterio que ve
         * el usuario en el sidebar. Evita el caso "visible en el menú, 403
         * al entrar" cuando un rol como ROLE_CARTERA cuelga fuera de la
         * jerarquía de ROLE_ADMIN pero el menú padre igual lo habilita.
         */
        puedeAccederRuta(ruta: string): boolean {
            return rutaEnArbol(this.menu, ruta);
        },
    },
});
