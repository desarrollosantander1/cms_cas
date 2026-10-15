import { fetchWrapper, type ArchivoDescargado } from '@/utils/helpers/fetch-wrapper';
import type { ClienteBusqueda, EnviarCorreoPayload, FacturaConSaldo, GenerarPdfPayload, TasaInteres } from '@/types/generadorCartas';

const baseUrl = `${import.meta.env.VITE_API_URL}/cartera/generador-cartas`;

export const generadorCartasService = {
    buscarClientes(q: string): Promise<ClienteBusqueda[]> {
        return fetchWrapper.get(`${baseUrl}/clientes?q=${encodeURIComponent(q)}`);
    },
    buscarFacturas(nit: string): Promise<FacturaConSaldo[]> {
        return fetchWrapper.get(`${baseUrl}/clientes/${encodeURIComponent(nit)}/facturas`);
    },
    /** Devuelve el PDF como blob (Content-Disposition: attachment). */
    generarPdf(payload: GenerarPdfPayload): Promise<ArchivoDescargado> {
        return fetchWrapper.download(`${baseUrl}/pdf`, payload);
    },
    enviarCorreo(payload: EnviarCorreoPayload): Promise<{ message: string }> {
        return fetchWrapper.post(`${baseUrl}/email`, payload);
    },
    verTasa(): Promise<TasaInteres> {
        return fetchWrapper.get(`${baseUrl}/tasa`);
    },
    actualizarTasa(tasaMensual: number): Promise<TasaInteres> {
        return fetchWrapper.put(`${baseUrl}/tasa`, { tasaMensual });
    }
};
