import type { ArchivoDescargado } from './fetch-wrapper';

/** Dispara la descarga de un Blob ya recibido (ej. fetchWrapper.download()) via un enlace temporal. */
export function descargarArchivo({ blob, nombreArchivo }: ArchivoDescargado): void {
    const url = URL.createObjectURL(blob);
    const enlace = document.createElement('a');
    enlace.href = url;
    enlace.download = nombreArchivo;
    document.body.appendChild(enlace);
    enlace.click();
    enlace.remove();
    URL.revokeObjectURL(url);
}
