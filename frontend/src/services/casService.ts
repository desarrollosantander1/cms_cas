import { fetchWrapper } from '@/utils/helpers/fetch-wrapper';

const baseUrl = `${import.meta.env.VITE_API_URL}/cas`;

export interface CasAdmin {
    id: number;
    nit: string | null;
    nombre: string | null;
    direccion: string | null;
    telefono: string | null;
    cartaCambioVence: number;
    estado: boolean;
    creadoEn: string;
    nitPropio: string | null;
    nombrePropio: string | null;
    direccionPropio: string | null;
    telefonoPropio: string | null;
    clienteSucursalId: number | null;
    clienteId: number | null;
    clienteNombre: string | null;
    sucursalNombre: string | null;
    ciudadId: number | null;
    ciudadNombre: string | null;
}

export interface CasUsuarioRow {
    id: number;
    cas: string;
    casId: number;
    usuarioId: number;
    usuario: string;
    documento: string | null;
    nombre: string;
    estado: boolean;
    creadoEn: string;
}

export interface CasUsuarioTabla {
    casUsuarioId: number;
    usuarioId: number;
    usuario: string;
    documento: string | null;
    nombre: string;
    casId: number;
    casNit: string | null;
    casNombre: string | null;
}

export interface CasUsuarioAsociado {
    id: number;
    documento: string | null;
    nombre: string | null;
    apellido: string | null;
    telefono: string | null;
    usuario: string;
    email: string;
    estado: boolean;
}

export interface CasAsociado {
    casId: number;
    casNombre: string | null;
}

export interface ClienteBusqueda {
    id: number;
    numeroIdentidad: string;
    nombre: string;
}

export interface SucursalDeCliente {
    id: number;
    nombre: string | null;
    direccion: string | null;
    telefono: string | null;
    ciudadId: number | null;
    ciudadNombre: string | null;
}

export interface CiudadCatalogo {
    id: number;
    nombre: string;
    departamento: string;
    pais: string;
}

export interface CrearCasPayload {
    clienteSucursalId?: number | null;
    ciudadId?: number | null;
    nit?: string | null;
    nombre?: string | null;
    direccion?: string | null;
    telefono?: string | null;
    cartaCambioVence?: number;
    estado?: boolean;
}

export interface AsociarUsuarioPayload {
    documento: string;
    nombre?: string;
    apellido?: string;
    telefono?: string | null;
    usuario?: string;
    email?: string;
    password?: string;
    estado?: boolean;
}

export const casService = {
    list(): Promise<CasAdmin[]> {
        return fetchWrapper.get(`${baseUrl}`);
    },
    crear(payload: CrearCasPayload): Promise<CasAdmin> {
        return fetchWrapper.post(`${baseUrl}`, payload);
    },
    actualizar(id: number, payload: CrearCasPayload): Promise<CasAdmin> {
        return fetchWrapper.put(`${baseUrl}/${id}`, payload);
    },
    usuariosDeCas(casId: number): Promise<CasUsuarioRow[]> {
        return fetchWrapper.get(`${baseUrl}/${casId}/usuarios`);
    },
    asociarUsuario(casId: number, payload: AsociarUsuarioPayload): Promise<CasUsuarioRow> {
        return fetchWrapper.post(`${baseUrl}/${casId}/usuarios`, payload);
    },
    listarUsuarios(): Promise<CasUsuarioTabla[]> {
        return fetchWrapper.get(`${baseUrl}/usuarios`);
    },
    buscarPorDocumento(documento: string): Promise<{ usuario: CasUsuarioAsociado | null; casAsociados: CasAsociado[] }> {
        return fetchWrapper.get(`${baseUrl}/usuarios/buscar?documento=${encodeURIComponent(documento)}`);
    },
    /**
     * `casId` identifica de cuál relación cas_usuario se trata: `estado` ya
     * no es el estado global de gbl.usuario, sino el de esa relación
     * puntual (ver CasUsuarioService::actualizarUsuario() en el backend).
     */
    actualizarUsuario(casId: number, usuarioId: number, payload: Partial<AsociarUsuarioPayload>): Promise<CasUsuarioRow> {
        return fetchWrapper.put(`${baseUrl}/${casId}/usuarios/${usuarioId}`, payload);
    },
    buscarClientes(q: string): Promise<ClienteBusqueda[]> {
        const query = q ? `?q=${encodeURIComponent(q)}` : '';
        return fetchWrapper.get(`${baseUrl}/clientes${query}`);
    },
    sucursalesDeCliente(clienteId: number): Promise<SucursalDeCliente[]> {
        return fetchWrapper.get(`${baseUrl}/clientes/${clienteId}/sucursales`);
    },
    listarCiudades(): Promise<CiudadCatalogo[]> {
        return fetchWrapper.get(`${baseUrl}/ciudades`);
    },
    listCasByUsuario(usuarioId: number): Promise<CasAdmin[]>{
        return fetchWrapper.get(`${baseUrl}/cas/${usuarioId}`);
    }
};
