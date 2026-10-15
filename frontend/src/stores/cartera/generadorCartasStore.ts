import { defineStore } from 'pinia';
import { generadorCartasService } from '@/services/generadorCartasService';
import type { ClienteBusqueda, EnviarCorreoPayload, FacturaConSaldo, GenerarPdfPayload, TasaInteres } from '@/types/generadorCartas';

export const useGeneradorCartasStore = defineStore('generadorCartas', {
    state: () => ({
        clientes: [] as ClienteBusqueda[],
        buscandoClientes: false,
        /** Cliente elegido en el autocompletar — en el store (no en un ref local de la vista) para que sobreviva al navegar a otro módulo y volver, igual que `facturas`. */
        clienteSeleccionado: null as ClienteBusqueda | null,
        facturas: [] as FacturaConSaldo[],
        buscandoFacturas: false,
        generandoPdf: false,
        enviandoCorreo: false,
        tasa: null as TasaInteres | null,
        cargandoTasa: false
    }),
    actions: {
        async buscarClientes(q: string) {
            if (q.trim().length < 2) {
                this.clientes = [];
                return;
            }
            this.buscandoClientes = true;
            try {
                this.clientes = await generadorCartasService.buscarClientes(q);
            } finally {
                this.buscandoClientes = false;
            }
        },
        async buscarFacturas(nit: string) {
            this.buscandoFacturas = true;
            this.facturas = [];
            try {
                this.facturas = await generadorCartasService.buscarFacturas(nit);
            } finally {
                this.buscandoFacturas = false;
            }
        },
        /** Genera el PDF y devuelve el blob — la vista arma el object URL para previsualizar/descargar. */
        async generarPdf(payload: GenerarPdfPayload) {
            this.generandoPdf = true;
            try {
                return await generadorCartasService.generarPdf(payload);
            } finally {
                this.generandoPdf = false;
            }
        },
        async enviarCorreo(payload: EnviarCorreoPayload) {
            this.enviandoCorreo = true;
            try {
                return await generadorCartasService.enviarCorreo(payload);
            } finally {
                this.enviandoCorreo = false;
            }
        },
        async cargarTasa() {
            this.cargandoTasa = true;
            try {
                this.tasa = await generadorCartasService.verTasa();
            } finally {
                this.cargandoTasa = false;
            }
        },
        /**
         * Ajusta la tasa solo para esta sesión (no llama al backend ni persiste en BD):
         * las cartas que se generen de aquí en adelante en esta pestaña usan este valor.
         * Al recargar la página se pierde y `cargarTasa()` vuelve a traer la de BD.
         */
        establecerTasaSesion(tasaMensual: number) {
            this.tasa = {
                tasaMensual,
                actualizadoEn: this.tasa?.actualizadoEn ?? null,
                actualizadoPor: this.tasa?.actualizadoPor ?? null,
                porDefecto: false
            };
        },
        limpiar() {
            this.clientes = [];
            this.clienteSeleccionado = null;
            this.facturas = [];
        }
    }
});
