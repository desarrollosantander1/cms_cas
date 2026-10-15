// Decodifica el payload de un JWT en el cliente, solo para leer claims como
// "exp" y saber cuándo expira. No valida la firma — eso ya lo hizo el backend
// al emitirlo/aceptarlo; en el navegador no hay nada que verificar.
export function decodeJwtPayload<T = any>(token: string): T | null {
    try {
        const [, payload] = token.split('.');
        const base64 = payload.replace(/-/g, '+').replace(/_/g, '/');
        const json = decodeURIComponent(
            atob(base64)
                .split('')
                .map((c) => '%' + c.charCodeAt(0).toString(16).padStart(2, '0'))
                .join(''),
        );
        return JSON.parse(json);
    } catch {
        return null;
    }
}

export function isJwtExpired(token: string): boolean {
    const payload = decodeJwtPayload<{ exp?: number }>(token);
    if (!payload?.exp) {
        return true;
    }
    return Date.now() >= payload.exp * 1000;
}
