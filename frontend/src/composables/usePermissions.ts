import { usePermissionsStore } from '@/stores/permissions';

/**
 * Fuente única de verdad de autorización en el frontend: v-can, guards de
 * router y cualquier v-if de permiso deben pasar por acá, nunca comparar
 * contra un rol o id a mano.
 */
export function usePermissions() {
    const store = usePermissionsStore();

    function can(codigo: string): boolean {
        return store.can(codigo);
    }

    return { can };
}
