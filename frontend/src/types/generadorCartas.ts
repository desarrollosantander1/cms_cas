/** Un cliente en el autocompletar de búsqueda del Generador de Cartas. */
export interface ClienteBusqueda {
    nit: string;
    nombre: string;
}

/** Una factura con saldo pendiente, tal como la lista el backend. */
export interface FacturaConSaldo {
    comprobante: string;
    fechaFactura: string;
    fechaVencimiento: string;
    valorInicial: number;
    saldo: number;
    diasMora: number;
    /** Correo de la sucursal de la factura — usado para prellenar el destinatario. */
    correo: string | null;
}

/** Los 3 tipos de carta jurídica de v1 (mismos strings que el backend). */
export type TipoCartaJuridica = 'data-credito' | 'prejurico-con-interes' | 'prejurico-sin-interes';

export interface GenerarPdfPayload {
    tipo: TipoCartaJuridica;
    nit: string;
    facturas: string[];
    /** Días extra sumados a la mora antes de calcular el interés — solo prejurídico con interés. */
    paramsDays?: number | null;
    /** Tasa de mora ajustada en esta sesión (no persistida en BD); si se omite, el backend usa la tasa vigente en BD. */
    tasaMensual?: number | null;
}

export interface EnviarCorreoPayload extends GenerarPdfPayload {
    destinatario: string;
}

export interface TasaInteres {
    tasaMensual: number;
    actualizadoEn: string | null;
    actualizadoPor: string | null;
    /** true = no hay tasa configurada, se usa el 2% por defecto. */
    porDefecto: boolean;
}

export const CARTAS: { tipo: TipoCartaJuridica; etiqueta: string; icono: string; color: string; admiteDias: boolean }[] = [
    { tipo: 'data-credito', etiqueta: 'Data crédito', icono: 'mdi-bank-outline', color: 'primary', admiteDias: false },
    { tipo: 'prejurico-con-interes', etiqueta: 'Prejurídico con interés', icono: 'mdi-gavel', color: 'warning', admiteDias: true },
    { tipo: 'prejurico-sin-interes', etiqueta: 'Prejurídico sin interés', icono: 'mdi-gavel', color: 'error', admiteDias: false }
];
