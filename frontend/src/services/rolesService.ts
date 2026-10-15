import { fetchWrapper } from '@/utils/helpers/fetch-wrapper';

const baseUrl = `${import.meta.env.VITE_API_URL}/users`;

/** Jerarquía de roles/permisos auto-referenciada — "role" es el único identificador, no hay catálogo aparte. */
export interface RolePermisoAdmin {
    id: number;
    role: string;
    rolePadreId: number | null;
    estado: boolean;
    /** 1 = raíz, 2 = hijo de una raíz, 3 = sub-hijo — tope de la jerarquía (ver RolePermisoService en el backend). */
    nivel: number;
}

export interface RolePermisoPayload {
    role: string;
    rolePadreId?: number | null;
}

export const rolesService = {
    list(): Promise<RolePermisoAdmin[]> {
        return fetchWrapper.get(`${baseUrl}/roles`);
    },
    crear(payload: RolePermisoPayload): Promise<RolePermisoAdmin> {
        return fetchWrapper.post(`${baseUrl}/roles`, payload);
    },
    actualizar(id: number, payload: Partial<RolePermisoPayload>): Promise<RolePermisoAdmin> {
        return fetchWrapper.put(`${baseUrl}/roles/${id}`, payload);
    },
    activar(id: number): Promise<RolePermisoAdmin> {
        return fetchWrapper.patch(`${baseUrl}/roles/${id}/activar`);
    },
    desactivar(id: number): Promise<RolePermisoAdmin> {
        return fetchWrapper.patch(`${baseUrl}/roles/${id}/desactivar`);
    },
};
