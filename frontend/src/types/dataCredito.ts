/** Periodo de cierre disponible para reportar a DataCrédito. */
export interface PeriodoCierre {
    id: number;
    fechaReporte: string;
    abierto: boolean;
}

/** Cliente con saldo dentro de un periodo — fila de la tabla principal de cada pestaña. */
export interface ClienteReporte {
    tercero: string;
    nit: string;
    cliente: string;
    tipo: string;
    saldoInicial: number;
    saldo: number;
}

/**
 * Obligación (factura) de un cliente dentro del modal de novedades.
 * `reportar`: 1 = se reporta a DataCrédito (default), 0 = se omite —
 * lo controla el checkbox "Omitir" de la fila.
 */
export interface FacturaCliente {
    facturaId: number;
    clienteId: number;
    datacreditoReporteCierreId: number;
    factura: string;
    fechaEmision: string;
    fechaVencimiento: string;
    valor: number;
    saldoAnterior: number;
    saldo: number;
    novedad: number;
    reportar: 0 | 1;
    diasVencidos?: number;
    /** Edad de la factura en días (fechaReporte - fechaEmision) — solo se muestra en la pestaña Datacrédito. */
    dias?: number;
}

/** Fila del modal "Ver todas y guardar" — cruza clientes, por eso trae también nit/cliente. */
export interface FacturaReclasificadaResumen extends FacturaCliente {
    nit: string;
    cliente: string;
}

export interface ResumenNovedadItem {
    novedad: number;
    cantidad: number;
}

export interface ResumenNovedades {
    totalRegistros: number;
    totalNovedades: number;
    resumen: ResumenNovedadItem[];
}

/** Catálogo de novedades filtrables en el modal del flujo normal (ver ContenidoComp de referencia). */
export const NOVEDADES_FILTRO: { texto: string; valor: number }[] = [
    { texto: 'Novedad 1', valor: 1 },
    { texto: 'Novedad 5', valor: 5 },
    { texto: 'Novedad 6', valor: 6 },
    { texto: 'Novedad 7', valor: 7 },
    { texto: 'Novedad 8', valor: 8 },
    { texto: 'Novedad 9', valor: 9 }
];

/** Novedad por defecto para facturas reclasificadas cuando no se especifica una válida (1–13). */
export const NOVEDAD_RECLASIFICADO_DEFECTO = 9;

/** Respuesta de POST /clientes/{tercero}/novedades — resumen de lo efectivamente guardado. */
export interface ResultadoGuardarNovedades {
    message: string;
    periodoId: number;
    fechaReporte: string;
    tercero: string;
    totalFacturas: number;
    creadas: number;
    actualizadas: number;
}

/** Respuesta de POST /cierre. */
export interface ResultadoCerrarPeriodo {
    message: string;
    periodoId: number;
    fechaReporte: string;
    clientesProcesados: number;
    facturasMaterializadas: number;
    facturasYaExistentes: number;
    totalFacturas: number;
}

/** Respuesta de POST /reclasificados/clientes/{tercero}/facturas. */
export interface ResultadoGuardarReclasificaciones {
    message: string;
    tercero: string;
    motivo: string | null;
    reclasificacionesCreadas: number;
    novedades: Omit<ResultadoGuardarNovedades, 'message'>;
}

/** Respuesta de POST /reclasificados/facturas — guardado masivo, uno o más terceros. */
export interface ResultadoGuardarTodasReclasificaciones {
    message: string;
    fechaReporte: string;
    totalTerceros: number;
    totalFacturas: number;
    reclasificacionesCreadas: number;
    porTercero: Omit<ResultadoGuardarReclasificaciones, 'message'>[];
}
