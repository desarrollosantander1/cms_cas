import { useAuthStore } from '@/stores/auth';

export interface ArchivoDescargado {
    blob: Blob;
    nombreArchivo: string;
}

export const fetchWrapper = {
    get: request('GET'),
    post: request('POST'),
    put: request('PUT'),
    patch: request('PATCH'),
    delete: request('DELETE'),
    /**
     * Para endpoints que devuelven un archivo (Content-Disposition:
     * attachment), no JSON — ej. /reportes/txt|excel|plano-excel. En error,
     * el backend igual responde JSON (ApiExceptionListener), así que ese
     * camino sí puede reusar el mismo parseo que handleResponse().
     */
    download: downloadRequest('POST')
};

function request(method: string) {
    // overrideToken: para llamadas que deben autorizarse con un token
    // distinto al activo (ver stores/auth.ts simular() — al encadenar una
    // simulación sobre otra, hay que autorizar contra el usuario original).
    return (url: any, body?: any, overrideToken?: string) => {
        const requestOptions: any = {
            method,
            headers: authHeader(url, overrideToken)
        };
        if (body) {
            requestOptions.headers['Content-Type'] = 'application/json';
            requestOptions.body = JSON.stringify(body);
        }
        return fetch(url, requestOptions).then(handleResponse);
    };
}

function downloadRequest(method: string) {
    return (url: any, body?: any, overrideToken?: string): Promise<ArchivoDescargado> => {
        const requestOptions: any = {
            method,
            headers: authHeader(url, overrideToken)
        };
        if (body) {
            requestOptions.headers['Content-Type'] = 'application/json';
            requestOptions.body = JSON.stringify(body);
        }
        return fetch(url, requestOptions).then(handleFileResponse);
    };
}

// helper functions

async function handleFileResponse(response: Response): Promise<ArchivoDescargado> {
    if (!response.ok) {
        // El error sí viene en JSON (ApiExceptionListener) — mismo manejo que handleResponse().
        const text = await response.text();
        const data = text && JSON.parse(text);

        if (response.status === 401) {
            const { token, logout } = useAuthStore();
            if (token) logout();
        }

        return Promise.reject((data && data.message) || response.statusText);
    }

    const blob = await response.blob();
    const disposition = response.headers.get('Content-Disposition') ?? '';
    const coincidencia = /filename="?([^";]+)"?/.exec(disposition);
    const nombreArchivo = coincidencia?.[1] ?? 'descarga';

    return { blob, nombreArchivo };
}

function authHeader(url: any, overrideToken?: string) {
    // return auth header with jwt if user is logged in and request is to the api url
    const { token: activeToken } = useAuthStore();
    const token = overrideToken ?? activeToken;
    const isLoggedIn = !!token;
    const isApiUrl = url.startsWith(import.meta.env.VITE_API_URL);
    if (isLoggedIn && isApiUrl) {
        return { Authorization: `Bearer ${token}` };
    } else {
        return {};
    }
}

function handleResponse(response: any) {
    return response.text().then((text: any) => {
        const data = text && JSON.parse(text);

        if (!response.ok) {
            const { token, logout } = useAuthStore();
            if (response.status === 401 && token) {
                // 401 = sesión inválida/expirada -> logout automático.
                // 403 (sin permiso) NO desloguea: es un error de autorización puntual.
                logout();
            }

            const error = (data && data.message) || response.statusText;
            return Promise.reject(error);
        }

        return data;
    });
}
