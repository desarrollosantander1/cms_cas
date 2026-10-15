import { defineStore } from 'pinia';
import { usersService, type UsuarioAdmin } from '@/services/usersService';
import { rolesService, type RolePermisoAdmin } from '@/services/rolesService';

export const useUsersStore = defineStore('users', {
    state: () => ({
        usuarios: [] as UsuarioAdmin[],
        roles: [] as RolePermisoAdmin[],
        loading: false,
    }),
    actions: {
        async cargarUsuarios(q = '') {
            this.loading = true;
            try {
                this.usuarios = await usersService.listUsuarios(q);
            } finally {
                this.loading = false;
            }
        },
        async cargarRoles() {
            const roles = await rolesService.list();
            this.roles = roles.filter((r) => r.estado);
        },
        async crear(payload: Parameters<typeof usersService.crearUsuario>[0]) {
            const usuario = await usersService.crearUsuario(payload);
            this.usuarios.unshift(usuario);
            return usuario;
        },
        async actualizar(id: number, payload: Parameters<typeof usersService.actualizarUsuario>[1]) {
            const actualizado = await usersService.actualizarUsuario(id, payload);
            this.reemplazar(actualizado);
            return actualizado;
        },
        async activar(id: number) {
            this.reemplazar(await usersService.activarUsuario(id));
        },
        async desactivar(id: number) {
            this.reemplazar(await usersService.desactivarUsuario(id));
        },
        async asignarRol(usuarioId: number, rolePermisoId: number) {
            await usersService.asignarRol(usuarioId, rolePermisoId);
            await this.cargarUsuarios();
        },
        async quitarRol(usuarioId: number, rolePermisoId: number) {
            await usersService.quitarRol(usuarioId, rolePermisoId);
            await this.cargarUsuarios();
        },
        reemplazar(usuario: UsuarioAdmin) {
            const idx = this.usuarios.findIndex((u) => u.id === usuario.id);
            if (idx !== -1) {
                this.usuarios[idx] = usuario;
            }
        },
    },
});
