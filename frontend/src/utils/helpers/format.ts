import { format, parseISO } from 'date-fns';

const formateadorMoneda = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0
});

export function formatCurrency(valor: number): string {
    return formateadorMoneda.format(valor || 0);
}

export function formatFecha(valor: string | Date): string {
    // El backend manda fechas "YYYY-MM-DD" (sin hora) — new Date(valor) las
    // interpreta como medianoche UTC, así que en cualquier zona horaria
    // detrás de UTC (ej. America/Bogota, UTC-5) se mostraba el día anterior.
    // parseISO() sí las interpreta en hora local, evitando ese corrimiento.
    const fecha = typeof valor === 'string' ? parseISO(valor) : valor;

    return format(fecha, 'dd/MM/yyyy');
}
