import { createRouter, createWebHistory } from 'vue-router';
import MainRoutes from './MainRoutes';
import AuthRoutes from './AuthRoutes';
import { useAuthStore } from '@/stores/auth';
import { usePermissionsStore } from '@/stores/permissions';

export const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/:pathMatch(.*)*',
            component: () => import('@/views/authentication/Error.vue')
        },
        MainRoutes,
        AuthRoutes
    ]
});

router.beforeEach(async (to) => {
    const requiresAuth = to.matched.some((record) => record.meta.requiresAuth);
    if (!requiresAuth) {
        return true;
    }

    const auth = useAuthStore();
    if (!auth.isAuthenticated) {
        auth.returnUrl = to.fullPath;
        return '/';
    }

    const permisoRequerido = to.meta.permission as string | undefined;
    if (!permisoRequerido) {
        return true;
    }

    // Vue Router resuelve la navegación inicial apenas se instala el router
    // (app.use(router)), sin esperar al bootstrap async de main.ts que carga
    // los permisos — en una entrada directa por URL a una ruta con permiso,
    // el guard puede correr antes de que /me haya respondido. Si
    // pasa eso, se espera acá mismo en vez de asumir que ya están cargados.
    const permissions = usePermissionsStore();
    if (!permissions.loaded) {
        await permissions.cargar();
    }

    const autorizado = permissions.can(permisoRequerido) || permissions.puedeAccederRuta(to.path);

    return autorizado ? true : { path: '/auth/403', query: { from: to.fullPath } };
});
