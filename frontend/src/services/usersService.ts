import { fetchWrapper } from '@/utils/helpers/fetch-wrapper';

const baseUrl = `${import.meta.env.VITE_API_URL}/users`;

export interface UsuarioAdmin {
    id: number;
    documento: string | null;
    nombre: string | null;
    apellido: string | null;
    telefono: string | null;
    usuario: string;
    email: string;
    estado: boolean;
    fallos: number;
    roles: { id: number; role: string }[];
}

export interface MenuNode {
    id: number;
    etiqueta: string;
    icono: string | null;
    ruta: string | null;
    children: MenuNode[];
}

export interface MenuGrupoNode {
    id: number;
    grupo: string;
    icono: string;
    children: MenuNode[];
}

/** Shape liviana de GET /usuarios/simulables — ver UsuarioController::simulables() en el backend. */
export interface UsuarioSimulable {
    id: number;
    usuario: string;
    nombre: string | null;
    apellido: string | null;
    email: string;
}

export interface CrearUsuarioPayload {
    documento: string;
    nombre: string;
    apellido: string;
    telefono?: string | null;
    usuario: string;
    email: string;
    password: string;
    politicaDatos: null;
}

export const usersService = {
    // usuarios
    listUsuarios(q = ''): Promise<UsuarioAdmin[]> {
        const query = q ? `?q=${encodeURIComponent(q)}` : '';
        return fetchWrapper.get(`${baseUrl}/usuarios${query}`);
    },
    getUsuario(id: number): Promise<UsuarioAdmin> {
        return fetchWrapper.get(`${baseUrl}/usuarios/${id}`);
    },
    crearUsuario(payload: CrearUsuarioPayload): Promise<UsuarioAdmin> {
        return fetchWrapper.post(`${baseUrl}/usuarios`, payload);
    },
    actualizarUsuario(id: number, payload: Partial<Omit<CrearUsuarioPayload, 'password' | 'politicaDatos'>>): Promise<UsuarioAdmin> {
        return fetchWrapper.put(`${baseUrl}/usuarios/${id}`, payload);
    },
    activarUsuario(id: number): Promise<UsuarioAdmin> {
        return fetchWrapper.patch(`${baseUrl}/usuarios/${id}/activar`);
    },
    desactivarUsuario(id: number): Promise<UsuarioAdmin> {
        return fetchWrapper.patch(`${baseUrl}/usuarios/${id}/desactivar`);
    },
    cambiarPassword(id: number, password: string): Promise<{ message: string }> {
        return fetchWrapper.patch(`${baseUrl}/usuarios/${id}/password`, { password });
    },
    asignarRol(usuarioId: number, rolePermisoId: number): Promise<{ message: string }> {
        return fetchWrapper.post(`${baseUrl}/usuarios/${usuarioId}/roles/${rolePermisoId}`);
    },
    quitarRol(usuarioId: number, rolePermisoId: number): Promise<{ message: string }> {
        return fetchWrapper.delete(`${baseUrl}/usuarios/${usuarioId}/roles/${rolePermisoId}`);
    },
    permisosEfectivos(usuarioId: number): Promise<{ roles: string[] }> {
        return fetchWrapper.get(`${baseUrl}/usuarios/${usuarioId}/permisos-efectivos`);
    },
    /**
     * Requiere ROLE_SIMULAR_USUARIO — para el selector de "simular usuario"
     * de la cabecera. `comoToken`: ver authService.impersonar().
     */
    simulables(comoToken?: string): Promise<UsuarioSimulable[]> {
        return fetchWrapper.get(`${baseUrl}/usuarios/simulables`, undefined, comoToken);
    },

    // sesión propia — una sola petición para roles efectivos + menú podado
    // (antes eran /me/permisos y /me/menu por separado)
    miSesion(): Promise<{ roles: string[]; menu: MenuGrupoNode[] }> {
        return fetchWrapper.get(`${baseUrl}/me`);
    },
};
