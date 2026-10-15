import { defineStore } from 'pinia';
import {
    casService,
    type AsociarUsuarioPayload,
    type CasAdmin,
    type CasUsuarioRow,
    type CasUsuarioTabla,
    type CiudadCatalogo,
    type CrearCasPayload
} from '@/services/casService';

export const useCasStore = defineStore('cas', {
    state: () => ({
        casList: [] as CasAdmin[],
        casUsuariosList: [] as CasUsuarioTabla[],
        ciudades: [] as CiudadCatalogo[],
        loading: false,
        loadingUsuarios: false
    }),
    actions: {
        async cargarCas() {
            this.loading = true;
            try {
                this.casList = await casService.list();
            } finally {
                this.loading = false;
            }
        },
        async cargarCiudades() {
            this.ciudades = await casService.listarCiudades();
        },
        async crear(payload: CrearCasPayload) {
            const cas = await casService.crear(payload);
            this.casList.unshift(cas);
            return cas;
        },
        async actualizar(id: number, payload: CrearCasPayload) {
            const actualizado = await casService.actualizar(id, payload);
            const idx = this.casList.findIndex((c) => c.id === id);
            if (idx !== -1) this.casList[idx] = actualizado;
            return actualizado;
        },
        async usuariosDeCas(casId: number): Promise<CasUsuarioRow[]> {
            return casService.usuariosDeCas(casId);
        },
        async asociarUsuario(casId: number, payload: AsociarUsuarioPayload) {
            return casService.asociarUsuario(casId, payload);
        },
        async cargarUsuariosTodos() {
            this.loadingUsuarios = true;
            try {
                this.casUsuariosList = await casService.listarUsuarios();
            } finally {
                this.loadingUsuarios = false;
            }
        }
    }
});
