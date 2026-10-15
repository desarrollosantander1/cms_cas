import { fetchWrapper, type ArchivoDescargado } from '@/utils/helpers/fetch-wrapper';
import type {
    ClienteReporte,
    FacturaCliente,
    FacturaReclasificadaResumen,
    PeriodoCierre,
    ResultadoCerrarPeriodo,
    ResultadoGuardarNovedades,
    ResultadoGuardarReclasificaciones,
    ResultadoGuardarTodasReclasificaciones,
    ResumenNovedades
} from '@/types/dataCredito';

const baseUrl = `${import.meta.env.VITE_API_URL}/cartera/datacredito`;

export interface GuardarNovedadesPayload {
    tercero: string;
    fechaReporte: string;
    facturas: FacturaCliente[];
    /** Solo lo usa guardarReclasificaciones() — motivo de la reclasificación, opcional. */
    motivo?: string;
}

export interface CerrarPeriodoPayload {
    periodoId: number;
    fechaReporte: string;
}

export const dataCreditoService = {
    // --- flujo núcleo (DataCrédito) ---
    listarPeriodos(): Promise<PeriodoCierre[]> {
        return fetchWrapper.get(`${baseUrl}/periodos`);
    },
    guardarFechaCierre(fechaReporte: string): Promise<PeriodoCierre> {
        return fetchWrapper.post(`${baseUrl}/periodos`, { fechaReporte });
    },
    listarClientes(fechaReporte: string): Promise<ClienteReporte[]> {
        return fetchWrapper.get(`${baseUrl}/clientes?fechaReporte=${encodeURIComponent(fechaReporte)}`);
    },
    listarFacturasCliente(tercero: string, fechaReporte: string): Promise<FacturaCliente[]> {
        return fetchWrapper.get(`${baseUrl}/clientes/${encodeURIComponent(tercero)}/facturas?fechaReporte=${encodeURIComponent(fechaReporte)}`);
    },
    resumenNovedades(fechaReporte: string): Promise<ResumenNovedades> {
        return fetchWrapper.get(`${baseUrl}/resumen-novedades?fechaReporte=${encodeURIComponent(fechaReporte)}`);
    },
    guardarNovedades(payload: GuardarNovedadesPayload): Promise<ResultadoGuardarNovedades> {
        return fetchWrapper.post(`${baseUrl}/clientes/${encodeURIComponent(payload.tercero)}/novedades`, payload);
    },

    cerrarPeriodo(payload: CerrarPeriodoPayload): Promise<ResultadoCerrarPeriodo> {
        return fetchWrapper.post(`${baseUrl}/cierre`, payload);
    },
    // --- generación de archivos: txt/plano-excel exigen período cerrado
    // (ROLE_DATACREDITO_CERRAR en el backend); excel acepta abierto también
    // (ROLE_DATACREDITO_VER) — ver ReporteController.
    generarTxt(fechaReporte: string): Promise<ArchivoDescargado> {
        return fetchWrapper.download(`${baseUrl}/reportes/txt`, { fechaReporte });
    },
    generarExcel(fechaReporte: string): Promise<ArchivoDescargado> {
        return fetchWrapper.download(`${baseUrl}/reportes/excel`, { fechaReporte });
    },
    generarPlanoExcel(fechaReporte: string): Promise<ArchivoDescargado> {
        return fetchWrapper.download(`${baseUrl}/reportes/plano-excel`, { fechaReporte });
    },

    // --- flujo de reclasificados ---
    listarClientesReclasificados(fechaReporte: string): Promise<ClienteReporte[]> {
        return fetchWrapper.get(`${baseUrl}/reclasificados/clientes?fechaReporte=${encodeURIComponent(fechaReporte)}`);
    },
    listarFacturasReclasificadas(tercero: string, fechaReporte: string): Promise<FacturaCliente[]> {
        return fetchWrapper.get(
            `${baseUrl}/reclasificados/clientes/${encodeURIComponent(tercero)}/facturas?fechaReporte=${encodeURIComponent(fechaReporte)}`
        );
    },
    guardarReclasificaciones(payload: GuardarNovedadesPayload): Promise<ResultadoGuardarReclasificaciones> {
        return fetchWrapper.post(`${baseUrl}/reclasificados/clientes/${encodeURIComponent(payload.tercero)}/facturas`, payload);
    },
    listarTodasFacturasReclasificadas(fechaReporte: string): Promise<FacturaReclasificadaResumen[]> {
        return fetchWrapper.get(`${baseUrl}/reclasificados/facturas?fechaReporte=${encodeURIComponent(fechaReporte)}`);
    },
    /** Guardado masivo desde el modal "Ver todas y guardar" — varias facturas, potencialmente de varios clientes. */
    guardarTodasReclasificaciones(fechaReporte: string, facturas: FacturaCliente[]): Promise<ResultadoGuardarTodasReclasificaciones> {
        return fetchWrapper.post(`${baseUrl}/reclasificados/facturas`, { fechaReporte, facturas });
    }
};
