import { fetchWrapper } from '@/utils/helpers/fetch-wrapper';

const baseUrl = `${import.meta.env.VITE_API_URL}/users`;

export interface MenuGrupoAdmin {
    id: number;
    grupo: string;
    icono: string;
    estado: boolean;
    orden: number;
}

export interface MenuAdmin {
    id: number;
    menu: string;
    menuGrupoId: number;
    menuPadreId: number | null;
    icono: string | null;
    ruta: string | null;
    estado: boolean;
    orden: number;
}

/** Nodo del árbol de gestión (GET /menus) — a diferencia de MenuNode (sidebar), trae estado, orden y las FK crudas. */
export interface MenuNodeAdmin {
    id: number;
    etiqueta: string;
    icono: string | null;
    ruta: string | null;
    estado: boolean;
    orden: number;
    menuGrupoId: number;
    menuPadreId: number | null;
    children: MenuNodeAdmin[];
}

export interface MenuGrupoNodeAdmin {
    id: number;
    grupo: string;
    icono: string;
    estado: boolean;
    orden: number;
    children: MenuNodeAdmin[];
}

export interface MenuGrupoPayload {
    grupo: string;
    icono: string;
    orden?: number | null;
}

export interface MenuPayload {
    menu: string;
    menuGrupoId?: number | null;
    menuPadreId?: number | null;
    icono?: string | null;
    ruta?: string | null;
    orden?: number | null;
}

export const menuService = {
    // grupos de menú
    listGrupos(): Promise<MenuGrupoAdmin[]> {
        return fetchWrapper.get(`${baseUrl}/menu-grupos`);
    },
    crearGrupo(payload: MenuGrupoPayload): Promise<MenuGrupoAdmin> {
        return fetchWrapper.post(`${baseUrl}/menu-grupos`, payload);
    },
    actualizarGrupo(id: number, payload: Partial<MenuGrupoPayload>): Promise<MenuGrupoAdmin> {
        return fetchWrapper.put(`${baseUrl}/menu-grupos/${id}`, payload);
    },
    activarGrupo(id: number): Promise<MenuGrupoAdmin> {
        return fetchWrapper.patch(`${baseUrl}/menu-grupos/${id}/activar`);
    },
    desactivarGrupo(id: number): Promise<MenuGrupoAdmin> {
        return fetchWrapper.patch(`${baseUrl}/menu-grupos/${id}/desactivar`);
    },
    /** Borrado físico: elimina el grupo y, en cascada, sus menús, submenús y relaciones de rol. */
    eliminarGrupo(id: number): Promise<{ message: string }> {
        return fetchWrapper.delete(`${baseUrl}/menu-grupos/${id}`);
    },

    // menús (árbol de gestión)
    arbol(): Promise<MenuGrupoNodeAdmin[]> {
        return fetchWrapper.get(`${baseUrl}/menus`);
    },
    crearMenu(payload: MenuPayload): Promise<MenuAdmin> {
        return fetchWrapper.post(`${baseUrl}/menus`, payload);
    },
    actualizarMenu(id: number, payload: Partial<MenuPayload>): Promise<MenuAdmin> {
        return fetchWrapper.put(`${baseUrl}/menus/${id}`, payload);
    },
    activarMenu(id: number): Promise<MenuAdmin> {
        return fetchWrapper.patch(`${baseUrl}/menus/${id}/activar`);
    },
    desactivarMenu(id: number): Promise<MenuAdmin> {
        return fetchWrapper.patch(`${baseUrl}/menus/${id}/desactivar`);
    },
    /** Borrado físico: elimina el menú y, si es padre, en cascada sus submenús y las relaciones de rol de ambos. */
    eliminarMenu(id: number): Promise<{ message: string }> {
        return fetchWrapper.delete(`${baseUrl}/menus/${id}`);
    },

    // roles asociados a un menú
    rolesDeMenu(menuId: number): Promise<{ id: number; role: string }[]> {
        return fetchWrapper.get(`${baseUrl}/menus/${menuId}/roles`);
    },
    asociarRol(menuId: number, rolePermisoId: number): Promise<{ message: string }> {
        return fetchWrapper.post(`${baseUrl}/menus/${menuId}/roles/${rolePermisoId}`);
    },
    desasociarRol(menuId: number, rolePermisoId: number): Promise<{ message: string }> {
        return fetchWrapper.delete(`${baseUrl}/menus/${menuId}/roles/${rolePermisoId}`);
    },
};
