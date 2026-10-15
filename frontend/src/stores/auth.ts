import { defineStore } from 'pinia';
import { router } from '@/router';
import { authService, type Usuario } from '@/services/authService';
import { usePermissionsStore } from '@/stores/permissions';
import { isJwtExpired } from '@/utils/helpers/jwt';

const STORAGE_KEY = 'heos_token';
const IMPERSONADOR_TOKEN_KEY = 'heos_impersonador_token';
const IMPERSONADOR_USUARIO_KEY = 'heos_impersonador_usuario';

function leerImpersonadorUsuario(): Usuario | null {
    const raw = localStorage.getItem(IMPERSONADOR_USUARIO_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw) as Usuario;
    } catch {
        return null;
    }
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        token: localStorage.getItem(STORAGE_KEY) as string | null,
        usuario: null as Usuario | null,
        returnUrl: null as string | null,
        /** Token/usuario originales, guardados mientras se simula a alguien más — ver simular()/volverAUsuarioPrincipal(). */
        impersonadorToken: localStorage.getItem(IMPERSONADOR_TOKEN_KEY) as string | null,
        impersonadorUsuario: leerImpersonadorUsuario(),
    }),
    getters: {
        isAuthenticated: (state) => !!state.token && !isJwtExpired(state.token),
        nombreCompleto: (state) => (state.usuario ? `${state.usuario.nombre} ${state.usuario.apellido}`.trim() : ''),
        /** Iniciales para el avatar — no hay foto de perfil en el modelo de usuario. */
        iniciales: (state) => {
            if (!state.usuario) return '';
            return `${state.usuario.nombre?.[0] ?? ''}${state.usuario.apellido?.[0] ?? ''}`.toUpperCase();
        },
        /** Solo true mientras se está simulando a un usuario distinto del principal. */
        estaSimulando: (state) => !!state.impersonadorToken,
    },
    actions: {
        async login(username: string, password: string) {
            const { token } = await authService.login(username, password);
            this.setToken(token);
            this.usuario = await authService.me();
            await usePermissionsStore().cargar();
            router.push(this.returnUrl || '/dashboards/modern');
        },
        async logout() {
            try {
                if (this.token) {
                    await authService.logout();
                }
            } catch {
                // JWT es stateless: si el logout remoto falla igual limpiamos localmente
            }
            this.setToken(null);
            this.usuario = null;
            this.limpiarImpersonador();
            usePermissionsStore().limpiar();
            // La pantalla de login real vive en "/" (Boxed Login), no en
            // "/auth/login" — esa ruta no existe en AuthRoutes.ts.
            router.push('/');
        },
        setToken(token: string | null) {
            this.token = token;
            if (token) {
                localStorage.setItem(STORAGE_KEY, token);
            } else {
                localStorage.removeItem(STORAGE_KEY);
            }
        },
        /**
         * Emite un JWT para `usuarioId` (requiere ROLE_SIMULAR_USUARIO, lo valida
         * el backend) y lo deja activo. Se puede llamar estando ya en una
         * simulación — para cambiar de usuario simulado sin volver primero al
         * principal — y en ese caso NO se vuelve a guardar el token activo
         * como "original" (eso pisaría al usuario principal real con uno
         * intermedio); el que ya está guardado sigue siendo el válido para
         * volver.
         *
         * La autorización siempre se evalúa contra el usuario ORIGINAL (el
         * único que puede tener ROLE_SIMULAR_USUARIO): al encadenar, el
         * token activo ya es el del usuario simulado anterior, que
         * normalmente no tiene ese permiso, así que se manda explícitamente
         * el token guardado en vez del activo.
         */
        async simular(usuarioId: number) {
            if (!this.impersonadorToken) {
                this.impersonadorToken = this.token;
                this.impersonadorUsuario = this.usuario;
                if (this.impersonadorToken) localStorage.setItem(IMPERSONADOR_TOKEN_KEY, this.impersonadorToken);
                if (this.impersonadorUsuario) localStorage.setItem(IMPERSONADOR_USUARIO_KEY, JSON.stringify(this.impersonadorUsuario));
            }

            const { token, usuario } = await authService.impersonar(usuarioId, this.impersonadorToken ?? undefined);
            this.setToken(token);
            this.usuario = usuario;

            const permissions = usePermissionsStore();
            permissions.limpiar();
            await permissions.cargar();

            router.push('/dashboards/modern');
        },
        /** Restaura la sesión del usuario principal tras una simulación. */
        async volverAUsuarioPrincipal() {
            if (!this.estaSimulando) return;

            this.setToken(this.impersonadorToken);
            this.usuario = this.impersonadorUsuario;
            this.limpiarImpersonador();

            const permissions = usePermissionsStore();
            permissions.limpiar();
            await permissions.cargar();

            router.push('/dashboards/modern');
        },
        limpiarImpersonador() {
            this.impersonadorToken = null;
            this.impersonadorUsuario = null;
            localStorage.removeItem(IMPERSONADOR_TOKEN_KEY);
            localStorage.removeItem(IMPERSONADOR_USUARIO_KEY);
        },
        /** Rehidrata el perfil desde /me al refrescar la página, si el token sigue vigente. */
        async rehydrate() {
            if (!this.token || isJwtExpired(this.token)) {
                this.setToken(null);
                this.usuario = null;
                return;
            }
            try {
                this.usuario = await authService.me();
            } catch {
                this.setToken(null);
                this.usuario = null;
            }
        },
    },
});
