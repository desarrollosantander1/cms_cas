import { defineStore } from 'pinia';
import { dataCreditoService, type CerrarPeriodoPayload, type GuardarNovedadesPayload } from '@/services/dataCreditoService';
import type { ClienteReporte, FacturaCliente, FacturaReclasificadaResumen, PeriodoCierre, ResumenNovedades } from '@/types/dataCredito';
import { descargarArchivo } from '@/utils/helpers/descargarArchivo';

const RESUMEN_VACIO: ResumenNovedades = { totalRegistros: 0, totalNovedades: 0, resumen: [] };

export const useDataCreditoStore = defineStore('dataCredito', {
    state: () => ({
        // periodos: compartidos por las dos pestañas
        periodos: [] as PeriodoCierre[],
        loadingPeriodos: false,

        // flujo núcleo (pestaña DataCrédito)
        clientes: [] as ClienteReporte[],
        buscandoClientes: false,
        facturasCliente: [] as FacturaCliente[],
        buscandoFacturas: false,
        guardandoNovedades: false,
        resumenNovedades: RESUMEN_VACIO as ResumenNovedades,
        cerrandoPeriodo: false,
        generandoTxt: false,
        generandoExcel: false,
        generandoPlanoExcel: false,
        reclasificandoFactura: false,

        // flujo de reclasificados
        clientesReclasificados: [] as ClienteReporte[],
        buscandoClientesReclasificados: false,
        facturasReclasificadas: [] as FacturaCliente[],
        buscandoFacturasReclasificadas: false,
        guardandoReclasificaciones: false,
        todasFacturasReclasificadas: [] as FacturaReclasificadaResumen[],
        cargandoTodasFacturas: false,
        guardandoTodasFacturas: false
    }),
    actions: {
        async cargarPeriodos() {
            this.loadingPeriodos = true;
            try {
                this.periodos = await dataCreditoService.listarPeriodos();
            } finally {
                this.loadingPeriodos = false;
            }
        },

        // --- flujo núcleo ---
        async buscarClientes(fechaReporte: string) {
            this.buscandoClientes = true;
            this.resumenNovedades = RESUMEN_VACIO;
            try {
                this.clientes = await dataCreditoService.listarClientes(fechaReporte);
                this.resumenNovedades = await dataCreditoService.resumenNovedades(fechaReporte);
            } finally {
                this.buscandoClientes = false;
            }
        },
        async buscarFacturasCliente(tercero: string, fechaReporte: string) {
            this.buscandoFacturas = true;
            try {
                this.facturasCliente = await dataCreditoService.listarFacturasCliente(tercero, fechaReporte);
            } finally {
                this.buscandoFacturas = false;
            }
        },
        async guardarNovedades(payload: GuardarNovedadesPayload) {
            this.guardandoNovedades = true;
            try {
                await dataCreditoService.guardarNovedades(payload);
                this.facturasCliente = [];
                await this.buscarClientes(payload.fechaReporte);
            } finally {
                this.guardandoNovedades = false;
            }
        },
        async cerrarPeriodo(payload: CerrarPeriodoPayload) {
            this.cerrandoPeriodo = true;
            try {
                await dataCreditoService.cerrarPeriodo(payload);
                await this.cargarPeriodos();
                await this.buscarClientes(payload.fechaReporte);
            } finally {
                this.cerrandoPeriodo = false;
            }
        },
        async generarTxt(fechaReporte: string) {
            this.generandoTxt = true;
            try {
                descargarArchivo(await dataCreditoService.generarTxt(fechaReporte));
            } finally {
                this.generandoTxt = false;
            }
        },
        async generarExcel(fechaReporte: string) {
            this.generandoExcel = true;
            try {
                descargarArchivo(await dataCreditoService.generarExcel(fechaReporte));
            } finally {
                this.generandoExcel = false;
            }
        },
        async generarPlanoExcel(fechaReporte: string) {
            this.generandoPlanoExcel = true;
            try {
                descargarArchivo(await dataCreditoService.generarPlanoExcel(fechaReporte));
            } finally {
                this.generandoPlanoExcel = false;
            }
        },
        /**
         * Crea la PRIMERA reclasificación de una factura desde la pestaña
         * normal (botón "Reclasificar" del modal de marcar novedades) —
         * pega al mismo endpoint que guardarReclasificaciones(), pero
         * refresca el estado de ESTA pestaña (clientes/facturasCliente), no
         * el de la pestaña Reclasificados.
         */
        async reclasificarFactura(payload: { tercero: string; fechaReporte: string; factura: FacturaCliente; motivo?: string }) {
            this.reclasificandoFactura = true;
            try {
                await dataCreditoService.guardarReclasificaciones({
                    tercero: payload.tercero,
                    fechaReporte: payload.fechaReporte,
                    facturas: [payload.factura],
                    motivo: payload.motivo
                });
                await this.buscarFacturasCliente(payload.tercero, payload.fechaReporte);
                await this.buscarClientes(payload.fechaReporte);
            } finally {
                this.reclasificandoFactura = false;
            }
        },

        // --- flujo de reclasificados ---
        async buscarClientesReclasificados(fechaReporte: string) {
            this.buscandoClientesReclasificados = true;
            try {
                this.clientesReclasificados = await dataCreditoService.listarClientesReclasificados(fechaReporte);
            } finally {
                this.buscandoClientesReclasificados = false;
            }
        },
        async buscarFacturasReclasificadas(tercero: string, fechaReporte: string) {
            this.buscandoFacturasReclasificadas = true;
            try {
                this.facturasReclasificadas = await dataCreditoService.listarFacturasReclasificadas(tercero, fechaReporte);
            } finally {
                this.buscandoFacturasReclasificadas = false;
            }
        },
        async guardarReclasificaciones(payload: GuardarNovedadesPayload) {
            this.guardandoReclasificaciones = true;
            try {
                await dataCreditoService.guardarReclasificaciones(payload);
                this.facturasReclasificadas = [];
                await this.buscarClientesReclasificados(payload.fechaReporte);
            } finally {
                this.guardandoReclasificaciones = false;
            }
        },
        async cargarTodasFacturasReclasificadas(fechaReporte: string) {
            this.cargandoTodasFacturas = true;
            try {
                this.todasFacturasReclasificadas = await dataCreditoService.listarTodasFacturasReclasificadas(fechaReporte);
            } finally {
                this.cargandoTodasFacturas = false;
            }
        },
        async guardarTodasFacturasReclasificadas(fechaReporte: string, facturas: FacturaCliente[]) {
            this.guardandoTodasFacturas = true;
            try {
                await dataCreditoService.guardarTodasReclasificaciones(fechaReporte, facturas);
                this.todasFacturasReclasificadas = [];
                await this.buscarClientesReclasificados(fechaReporte);
            } finally {
                this.guardandoTodasFacturas = false;
            }
        }
    }
});
