import { defineStore } from 'pinia';
import { menuService, type MenuGrupoAdmin, type MenuGrupoNodeAdmin } from '@/services/menuService';
import { rolesService, type RolePermisoAdmin } from '@/services/rolesService';

export const useMenuStore = defineStore('menu', {
    state: () => ({
        grupos: [] as MenuGrupoAdmin[],
        arbol: [] as MenuGrupoNodeAdmin[],
        roles: [] as RolePermisoAdmin[],
        loading: false,
    }),
    actions: {
        async cargarGrupos() {
            this.grupos = await menuService.listGrupos();
        },
        async cargarArbol() {
            this.loading = true;
            try {
                this.arbol = await menuService.arbol();
            } finally {
                this.loading = false;
            }
        },
        async cargarRoles() {
            const roles = await rolesService.list();
            this.roles = roles.filter((r) => r.estado);
        },
        async crearGrupo(payload: Parameters<typeof menuService.crearGrupo>[0]) {
            const grupo = await menuService.crearGrupo(payload);
            this.grupos.push(grupo);
            await this.cargarArbol();
            return grupo;
        },
        async actualizarGrupo(id: number, payload: Parameters<typeof menuService.actualizarGrupo>[1]) {
            const actualizado = await menuService.actualizarGrupo(id, payload);
            this.reemplazarGrupo(actualizado);
            await this.cargarArbol();
            return actualizado;
        },
        async activarGrupo(id: number) {
            this.reemplazarGrupo(await menuService.activarGrupo(id));
            await this.cargarArbol();
        },
        async desactivarGrupo(id: number) {
            this.reemplazarGrupo(await menuService.desactivarGrupo(id));
            await this.cargarArbol();
        },
        reemplazarGrupo(grupo: MenuGrupoAdmin) {
            const idx = this.grupos.findIndex((g) => g.id === grupo.id);
            if (idx !== -1) {
                this.grupos[idx] = grupo;
            }
        },
        async eliminarGrupo(id: number) {
            await menuService.eliminarGrupo(id);
            this.grupos = this.grupos.filter((g) => g.id !== id);
            await this.cargarArbol();
        },
        async crearMenu(payload: Parameters<typeof menuService.crearMenu>[0]) {
            const menu = await menuService.crearMenu(payload);
            await this.cargarArbol();
            return menu;
        },
        async actualizarMenu(id: number, payload: Parameters<typeof menuService.actualizarMenu>[1]) {
            const menu = await menuService.actualizarMenu(id, payload);
            await this.cargarArbol();
            return menu;
        },
        async activarMenu(id: number) {
            await menuService.activarMenu(id);
            await this.cargarArbol();
        },
        async desactivarMenu(id: number) {
            await menuService.desactivarMenu(id);
            await this.cargarArbol();
        },
        async eliminarMenu(id: number) {
            await menuService.eliminarMenu(id);
            await this.cargarArbol();
        },
    },
});
