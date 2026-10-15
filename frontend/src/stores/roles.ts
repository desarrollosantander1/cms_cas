import { defineStore } from 'pinia';
import { rolesService, type RolePermisoAdmin, type RolePermisoPayload } from '@/services/rolesService';

export const useRolesStore = defineStore('roles', {
    state: () => ({
        roles: [] as RolePermisoAdmin[],
        loading: false,
    }),
    actions: {
        async cargar() {
            this.loading = true;
            try {
                this.roles = await rolesService.list();
            } finally {
                this.loading = false;
            }
        },
        async crear(payload: RolePermisoPayload) {
            const rol = await rolesService.crear(payload);
            this.roles.push(rol);
            return rol;
        },
        async actualizar(id: number, payload: Partial<RolePermisoPayload>) {
            const actualizado = await rolesService.actualizar(id, payload);
            this.reemplazar(actualizado);
            return actualizado;
        },
        async activar(id: number) {
            this.reemplazar(await rolesService.activar(id));
        },
        async desactivar(id: number) {
            this.reemplazar(await rolesService.desactivar(id));
        },
        reemplazar(rol: RolePermisoAdmin) {
            const idx = this.roles.findIndex((r) => r.id === rol.id);
            if (idx !== -1) {
                this.roles[idx] = rol;
            }
        },
    },
});
