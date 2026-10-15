import { fetchWrapper } from '@/utils/helpers/fetch-wrapper';

const baseUrl = `${import.meta.env.VITE_API_URL}/auth`;

export interface LoginResponse {
    token: string;
}

/** Shape real de GET /auth/me (ver SessionController::me() en el backend) — no inventar campos que el backend no devuelve. */
export interface Usuario {
    id: number;
    usuario: string;
    email: string;
    nombre: string;
    apellido: string;
    telefono: string | null;
    documento: string;
}

export const authService = {
    login(username: string, password: string): Promise<LoginResponse> {
        return fetchWrapper.post(`${baseUrl}/login`, { username, password });
    },
    me(): Promise<Usuario> {
        return fetchWrapper.get(`${baseUrl}/me`);
    },
    logout(): Promise<unknown> {
        return fetchWrapper.post(`${baseUrl}/logout`);
    },
    forgotPassword(usuario: string): Promise<{ message: string }> {
        return fetchWrapper.post(`${baseUrl}/password/forgot`, { usuario });
    },
    resetPassword(token: string, password: string): Promise<{ message: string }> {
        return fetchWrapper.post(`${baseUrl}/password/reset`, { token, password });
    },
    /**
     * Requiere ROLE_SIMULAR_USUARIO — lo valida el backend contra API users sf4-1,
     * no el frontend. `comoToken` permite autorizar con un token distinto al
     * activo: al encadenar una simulación sobre otra, el token activo ya es
     * el del usuario simulado (que normalmente no tiene el permiso), así
     * que hay que autorizar con el del usuario original.
     */
    impersonar(usuarioId: number, comoToken?: string): Promise<{ token: string; usuario: Usuario }> {
        return fetchWrapper.post(`${baseUrl}/impersonate/${usuarioId}`, undefined, comoToken);
    },
};
