<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import DataTable from '@/components/shared/DataTable.vue';
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue';
import MenuTreeNode from './menu/MenuTreeNode.vue';
import { useMenuStore } from '@/stores/menu';
import { menuService, type MenuGrupoAdmin, type MenuNodeAdmin } from '@/services/menuService';
import type { RolePermisoAdmin } from '@/services/rolesService';
import { PencilIcon, EyeIcon, EyeOffIcon, TrashIcon } from 'vue-tabler-icons';

const page = { title: 'Gestión de menú' };
const breadcrumbs = [
    { text: 'Configuración', disabled: true, href: '#' },
    { text: 'Administración', disabled: true, href: '#' },
    { text: 'Gestión de menú', disabled: true, href: '#' },
];

const store = useMenuStore();
const tab = ref('menus');
const snackbar = reactive({ show: false, color: 'success', text: '' });

function notificar(color: 'success' | 'error', text: string) {
    snackbar.color = color;
    snackbar.text = text;
    snackbar.show = true;
}

function mensajeError(e: unknown, fallback: string): string {
    return typeof e === 'string' ? e : fallback;
}

/** '' o null = sin indicar (el backend agrega al final); si no, el número elegido. */
function parseOrden(v: number | string | null): number | null {
    if (v === null || v === '') return null;
    const n = Number(v);
    return Number.isFinite(n) ? n : null;
}

onMounted(async () => {
    await Promise.all([store.cargarGrupos(), store.cargarArbol(), store.cargarRoles()]);
});

/* ---------------- grupos de menú ---------------- */

const gruposHeaders = [
    { title: 'Orden', key: 'orden', width: 90 },
    { title: 'Grupo', key: 'grupo' },
    { title: 'Ícono', key: 'icono' },
    { title: 'Estado', key: 'estado' },
    { title: 'Acciones', key: 'acciones', sortable: false },
];

const dialogGrupo = ref(false);
const editandoGrupo = ref<MenuGrupoAdmin | null>(null);
const formGrupo = reactive<{ grupo: string; icono: string; orden: number | string | null }>({ grupo: '', icono: '', orden: null });
const grupoFormRef = ref();
const grupoError = ref('');
const guardandoGrupo = ref(false);
const gruposCambiandoEstado = ref<Set<number>>(new Set());
const requeridoRule = (v: string) => !!v?.trim() || 'Este campo es obligatorio.';

function nuevoGrupo() {
    editandoGrupo.value = null;
    Object.assign(formGrupo, { grupo: '', icono: '', orden: null });
    grupoError.value = '';
    grupoFormRef.value?.resetValidation();
    dialogGrupo.value = true;
}

function editarGrupo(grupo: MenuGrupoAdmin) {
    editandoGrupo.value = grupo;
    Object.assign(formGrupo, { grupo: grupo.grupo, icono: grupo.icono, orden: grupo.orden });
    grupoError.value = '';
    grupoFormRef.value?.resetValidation();
    dialogGrupo.value = true;
}

async function guardarGrupo() {
    const { valid } = await grupoFormRef.value.validate();
    if (!valid) return;

    guardandoGrupo.value = true;
    grupoError.value = '';
    try {
        const payload = { grupo: formGrupo.grupo, icono: formGrupo.icono, orden: parseOrden(formGrupo.orden) };
        if (editandoGrupo.value) {
            await store.actualizarGrupo(editandoGrupo.value.id, payload);
        } else {
            await store.crearGrupo(payload);
        }
        notificar('success', editandoGrupo.value ? 'Grupo actualizado.' : 'Grupo creado.');
        dialogGrupo.value = false;
    } catch (e) {
        grupoError.value = mensajeError(e, 'No se pudo guardar el grupo.');
        notificar('error', grupoError.value);
    } finally {
        guardandoGrupo.value = false;
    }
}

async function toggleEstadoGrupo(grupo: MenuGrupoAdmin) {
    if (gruposCambiandoEstado.value.has(grupo.id)) return;
    gruposCambiandoEstado.value.add(grupo.id);
    try {
        if (grupo.estado) {
            await store.desactivarGrupo(grupo.id);
        } else {
            await store.activarGrupo(grupo.id);
        }
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo cambiar el estado del grupo.'));
    } finally {
        gruposCambiandoEstado.value.delete(grupo.id);
    }
}

const dialogEliminarGrupo = ref(false);
const grupoAEliminar = ref<MenuGrupoAdmin | null>(null);
const eliminandoGrupo = ref(false);

function confirmarEliminarGrupo(grupo: MenuGrupoAdmin) {
    grupoAEliminar.value = grupo;
    dialogEliminarGrupo.value = true;
}

async function eliminarGrupoConfirmado() {
    if (!grupoAEliminar.value) return;
    eliminandoGrupo.value = true;
    try {
        await store.eliminarGrupo(grupoAEliminar.value.id);
        notificar('success', `Grupo "${grupoAEliminar.value.grupo}" eliminado.`);
        dialogEliminarGrupo.value = false;
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo eliminar el grupo.'));
    } finally {
        eliminandoGrupo.value = false;
    }
}

/* ---------------- menús (árbol) ---------------- */

const dialogMenu = ref(false);
const editandoMenu = ref<MenuNodeAdmin | null>(null);
const formMenu = reactive<{
    menu: string;
    menuGrupoId: number | null;
    menuPadreId: number | null;
    icono: string;
    ruta: string;
    orden: number | string | null;
}>({
    menu: '',
    menuGrupoId: null,
    menuPadreId: null,
    icono: '',
    ruta: '',
    orden: null,
});
const menuFormRef = ref();
const menuError = ref('');
const guardandoMenu = ref(false);
const menusCambiandoEstado = ref<Set<number>>(new Set());

/** Todos los nodos del árbol, aplanados, para poblar el selector de "padre". */
const menusPlanos = computed(() => {
    const resultado: MenuNodeAdmin[] = [];
    function recorrer(nodos: MenuNodeAdmin[]) {
        for (const nodo of nodos) {
            resultado.push(nodo);
            recorrer(nodo.children);
        }
    }
    for (const grupo of store.arbol) recorrer(grupo.children);
    return resultado;
});

/** Al editar, un menú no puede terminar como padre de sí mismo ni de uno de sus descendientes. */
function idsDescendientes(nodo: MenuNodeAdmin): Set<number> {
    const ids = new Set<number>();
    function recorrer(hijos: MenuNodeAdmin[]) {
        for (const hijo of hijos) {
            ids.add(hijo.id);
            recorrer(hijo.children);
        }
    }
    recorrer(nodo.children);
    return ids;
}

/**
 * La jerarquía se limita a dos niveles: solo un menú de primer nivel
 * (sin padre propio) puede elegirse como padre de otro (ver
 * MenuService::validarNivelMaximo() en el backend).
 */
const opcionesPadre = computed(() => {
    const excluidos = editandoMenu.value ? new Set([editandoMenu.value.id, ...idsDescendientes(editandoMenu.value)]) : new Set<number>();
    return menusPlanos.value
        .filter((m) => m.menuPadreId === null && !excluidos.has(m.id))
        .map((m) => ({ id: m.id, etiqueta: m.etiqueta }));
});

const tienePadre = computed(() => formMenu.menuPadreId !== null);

/** Un menú que ya tiene submenús propios no puede convertirse en submenú de otro (sus hijos quedarían en un tercer nivel). */
const padreEditable = computed(() => !editandoMenu.value || editandoMenu.value.children.length === 0);

function nuevoMenu(padre?: MenuNodeAdmin) {
    if (padre && padre.menuPadreId !== null) return; // defensivo: el botón ya está deshabilitado en ese caso
    editandoMenu.value = null;
    Object.assign(formMenu, {
        menu: '',
        menuGrupoId: padre ? padre.menuGrupoId : null,
        menuPadreId: padre ? padre.id : null,
        icono: '',
        ruta: '',
        orden: null,
    });
    menuError.value = '';
    menuFormRef.value?.resetValidation();
    dialogMenu.value = true;
}

function editarMenu(nodo: MenuNodeAdmin) {
    editandoMenu.value = nodo;
    Object.assign(formMenu, {
        menu: nodo.etiqueta,
        menuGrupoId: nodo.menuGrupoId,
        menuPadreId: nodo.menuPadreId,
        icono: nodo.icono ?? '',
        ruta: nodo.ruta ?? '',
        orden: nodo.orden,
    });
    menuError.value = '';
    menuFormRef.value?.resetValidation();
    dialogMenu.value = true;
}

async function guardarMenu() {
    const { valid } = await menuFormRef.value.validate();
    if (!valid) return;

    guardandoMenu.value = true;
    menuError.value = '';
    try {
        const payload = {
            menu: formMenu.menu,
            menuPadreId: formMenu.menuPadreId,
            menuGrupoId: formMenu.menuPadreId ? null : formMenu.menuGrupoId,
            icono: formMenu.icono || null,
            ruta: formMenu.ruta || null,
            orden: parseOrden(formMenu.orden),
        };
        if (editandoMenu.value) {
            await store.actualizarMenu(editandoMenu.value.id, payload);
        } else {
            await store.crearMenu(payload);
        }
        notificar('success', editandoMenu.value ? 'Menú actualizado.' : 'Menú creado.');
        dialogMenu.value = false;
    } catch (e) {
        menuError.value = mensajeError(e, 'No se pudo guardar el menú.');
        notificar('error', menuError.value);
    } finally {
        guardandoMenu.value = false;
    }
}

async function toggleEstadoMenu(nodo: MenuNodeAdmin) {
    if (menusCambiandoEstado.value.has(nodo.id)) return;
    menusCambiandoEstado.value.add(nodo.id);
    try {
        if (nodo.estado) {
            await store.desactivarMenu(nodo.id);
        } else {
            await store.activarMenu(nodo.id);
        }
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo cambiar el estado del menú.'));
    } finally {
        menusCambiandoEstado.value.delete(nodo.id);
    }
}

const dialogEliminarMenu = ref(false);
const menuAEliminar = ref<MenuNodeAdmin | null>(null);
const eliminandoMenu = ref(false);

const textoConfirmarEliminarMenu = computed(() => {
    if (!menuAEliminar.value) return '';
    if (menuAEliminar.value.children.length > 0) {
        return `Vas a eliminar el menú "${menuAEliminar.value.etiqueta}". Sus ${menuAEliminar.value.children.length} submenú(s) y los roles asociados a él y a sus submenús también se eliminarán. Esta acción no se puede deshacer.`;
    }
    return `Vas a eliminar el menú "${menuAEliminar.value.etiqueta}". Los roles asociados a este menú también se eliminarán. Esta acción no se puede deshacer.`;
});

function confirmarEliminarMenu(nodo: MenuNodeAdmin) {
    menuAEliminar.value = nodo;
    dialogEliminarMenu.value = true;
}

async function eliminarMenuConfirmado() {
    if (!menuAEliminar.value) return;
    eliminandoMenu.value = true;
    try {
        await store.eliminarMenu(menuAEliminar.value.id);
        notificar('success', `Menú "${menuAEliminar.value.etiqueta}" eliminado.`);
        dialogEliminarMenu.value = false;
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo eliminar el menú.'));
    } finally {
        eliminandoMenu.value = false;
    }
}

/* ---------------- roles asociados a un menú ---------------- */

const dialogRoles = ref(false);
const menuRoles = ref<MenuNodeAdmin | null>(null);
const rolesAsociados = ref<Set<number>>(new Set());
const rolesCargando = ref(false);
const roleCambiando = ref<Set<number>>(new Set());

interface RolHijoAsociable {
    id: number;
    role: string;
    profundidad: number;
}

/**
 * Columna izquierda: roles de primer nivel (para menús padre). Columna
 * derecha: roles de segundo Y tercer nivel juntos, el nieto indentado bajo
 * su padre de nivel 2 — para menús hijos (ver MenuRolePermisoService en el
 * backend, que ahora permite nivel 3 ahí).
 */
const rolesAsociables = computed(() => {
    const porPadre = new Map<number | null, RolePermisoAdmin[]>();
    for (const rol of store.roles) {
        const clave = rol.rolePadreId;
        if (!porPadre.has(clave)) porPadre.set(clave, []);
        porPadre.get(clave)!.push(rol);
    }

    function hijosYNietos(padreId: number): RolHijoAsociable[] {
        const resultado: RolHijoAsociable[] = [];
        for (const hijo of porPadre.get(padreId) ?? []) {
            resultado.push({ id: hijo.id, role: hijo.role, profundidad: 0 });
            for (const nieto of porPadre.get(hijo.id) ?? []) {
                resultado.push({ id: nieto.id, role: nieto.role, profundidad: 1 });
            }
        }
        return resultado;
    }

    return (porPadre.get(null) ?? []).map((padre) => ({
        ...padre,
        hijos: hijosYNietos(padre.id),
    }));
});

/**
 * Un menú padre solo se asocia a roles de primer nivel (columna izquierda);
 * un submenú, a roles de segundo o tercer nivel (columna derecha, ver
 * MenuRolePermisoService en el backend — ahí es donde de verdad se exige).
 * Un rol ya asociado que no cumpla la regla (quedó de antes de esta
 * validación) se sigue mostrando marcado, pero no se puede tocar desde acá.
 */
const menuEsPadre = computed(() => menuRoles.value?.menuPadreId === null);
function nivel1Bloqueado(): boolean {
    return !menuEsPadre.value;
}
function nivelesHijoBloqueados(): boolean {
    return menuEsPadre.value;
}

async function verRoles(nodo: MenuNodeAdmin) {
    menuRoles.value = nodo;
    dialogRoles.value = true;
    rolesCargando.value = true;
    try {
        const roles = await menuService.rolesDeMenu(nodo.id);
        rolesAsociados.value = new Set(roles.map((r) => r.id));
    } finally {
        rolesCargando.value = false;
    }
}

function tieneRolAsociado(id: number): boolean {
    return rolesAsociados.value.has(id);
}

async function toggleRolAsociado(rol: { id: number; role: string }) {
    if (!menuRoles.value || roleCambiando.value.has(rol.id)) return;
    const yaAsociado = tieneRolAsociado(rol.id);
    roleCambiando.value.add(rol.id);
    try {
        if (yaAsociado) {
            await menuService.desasociarRol(menuRoles.value.id, rol.id);
            rolesAsociados.value.delete(rol.id);
        } else {
            await menuService.asociarRol(menuRoles.value.id, rol.id);
            rolesAsociados.value.add(rol.id);
        }
        notificar('success', yaAsociado ? `Rol "${rol.role}" desasociado.` : `Rol "${rol.role}" asociado.`);
        await store.cargarArbol();
    } catch (e) {
        notificar('error', mensajeError(e, `No se pudo ${yaAsociado ? 'desasociar' : 'asociar'} el rol "${rol.role}".`));
    } finally {
        roleCambiando.value.delete(rol.id);
    }
}
</script>

<template>
    <BaseBreadcrumb :title="page.title" :breadcrumbs="breadcrumbs"></BaseBreadcrumb>

    <v-card elevation="10">
        <v-tabs v-model="tab">
            <v-tab value="menus">Menús</v-tab>
            <v-tab value="grupos">Grupos de menú</v-tab>
        </v-tabs>
        <v-card-text>
            <v-window v-model="tab">
                <!-- ---------------- menús ---------------- -->
                <v-window-item value="menus">
                    <div class="d-flex justify-end mb-3">
                        <v-btn v-can="'ROLE_GESTIONAR_MENU'" color="primary" flat @click="nuevoMenu()">
                            <v-icon class="mr-2">mdi-plus</v-icon>Nuevo menú
                        </v-btn>
                    </div>

                    <div v-if="store.loading" class="text-center py-6"><v-progress-circular indeterminate color="primary" /></div>

                    <template v-else>
                        <div v-for="grupo in store.arbol" :key="grupo.id" class="mb-6">
                            <div class="d-flex align-center mb-2">
                                <v-icon class="mr-2">{{ grupo.icono || 'mdi-folder-outline' }}</v-icon>
                                <span class="text-subtitle-1 font-weight-bold">{{ grupo.grupo }}</span>
                                <v-chip class="ml-2" :color="grupo.estado ? 'success' : 'error'" size="small">{{
                                    grupo.estado ? 'Activo' : 'Inactivo'
                                }}</v-chip>
                            </div>
                            <v-table density="compact">
                                <thead>
                                    <tr>
                                        <th class="text-subtitle-2 font-weight-semibold" style="width: 70px">Orden</th>
                                        <th class="text-subtitle-2 font-weight-semibold">Menú</th>
                                        <th class="text-subtitle-2 font-weight-semibold">Ruta</th>
                                        <th class="text-subtitle-2 font-weight-semibold">Estado</th>
                                        <th class="text-subtitle-2 font-weight-semibold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <MenuTreeNode
                                        v-for="nodo in grupo.children"
                                        :key="nodo.id"
                                        :node="nodo"
                                        :cambiando-estado="menusCambiandoEstado"
                                        @editar="editarMenu"
                                        @roles="verRoles"
                                        @agregar-hijo="nuevoMenu"
                                        @toggle-estado="toggleEstadoMenu"
                                        @eliminar="confirmarEliminarMenu"
                                    />
                                    <tr v-if="grupo.children.length === 0">
                                        <td colspan="5" class="text-center text-medium-emphasis py-4">Sin menús en este grupo.</td>
                                    </tr>
                                </tbody>
                            </v-table>
                        </div>
                        <div v-if="store.arbol.length === 0" class="text-center text-medium-emphasis py-6">
                            Sin grupos de menú todavía — crea uno en la pestaña "Grupos de menú".
                        </div>
                    </template>
                </v-window-item>

                <!-- ---------------- grupos ---------------- -->
                <v-window-item value="grupos">
                    <DataTable title="Grupos de menú" :headers="gruposHeaders" :items="store.grupos">
                        <template v-slot:toolbar-actions>
                            <v-btn v-can="'ROLE_GESTIONAR_MENU'" color="primary" flat @click="nuevoGrupo">
                                <v-icon class="mr-2">mdi-plus</v-icon>Nuevo grupo
                            </v-btn>
                        </template>
                        <template v-slot:item.estado="{ item }">
                            <v-chip :color="item.estado ? 'success' : 'error'" size="small">{{ item.estado ? 'Activo' : 'Inactivo' }}</v-chip>
                        </template>
                        <template v-slot:item.acciones="{ item }">
                            <div class="d-flex align-center">
                                <v-tooltip text="Editar">
                                    <template v-slot:activator="{ props }">
                                        <v-btn icon flat v-bind="props" @click="editarGrupo(item)"
                                            ><PencilIcon stroke-width="1.5" size="20" class="text-primary"
                                        /></v-btn>
                                    </template>
                                </v-tooltip>
                                <v-tooltip :text="item.estado ? 'Desactivar' : 'Activar'">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            icon
                                            flat
                                            v-bind="props"
                                            :loading="gruposCambiandoEstado.has(item.id)"
                                            :disabled="gruposCambiandoEstado.has(item.id)"
                                            @click="toggleEstadoGrupo(item)"
                                            ><EyeOffIcon v-if="item.estado" stroke-width="1.5" size="20" class="text-error" /><EyeIcon
                                                v-else
                                                stroke-width="1.5"
                                                size="20"
                                                class="text-success"
                                        /></v-btn>
                                    </template>
                                </v-tooltip>
                                <v-tooltip text="Eliminar">
                                    <template v-slot:activator="{ props }">
                                        <v-btn v-can="'ROLE_ELIMINAR_GRUPO'" icon flat v-bind="props" @click="confirmarEliminarGrupo(item)"
                                            ><TrashIcon stroke-width="1.5" size="20" class="text-error"
                                        /></v-btn>
                                    </template>
                                </v-tooltip>
                            </div>
                        </template>
                    </DataTable>
                </v-window-item>
            </v-window>
        </v-card-text>
    </v-card>

    <!-- crear / editar grupo -->
    <v-dialog v-model="dialogGrupo" max-width="480px">
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h5">{{ editandoGrupo ? 'Editar grupo' : 'Nuevo grupo' }}</span>
            </v-card-title>
            <v-card-text>
                <v-form ref="grupoFormRef">
                    <v-row class="mt-1">
                        <v-col cols="12">
                            <v-text-field v-model="formGrupo.grupo" label="Nombre del grupo" :rules="[requeridoRule]" />
                        </v-col>
                        <v-col cols="12">
                            <v-text-field v-model="formGrupo.icono" label="Ícono (ej. mdi-cog)" :rules="[requeridoRule]" />
                        </v-col>
                        <v-col cols="12">
                            <v-text-field
                                v-model="formGrupo.orden"
                                type="number"
                                label="Orden"
                                hint="Vacío = se agrega al final. Menor primero."
                                persistent-hint
                            />
                        </v-col>
                    </v-row>
                </v-form>
                <v-alert v-if="grupoError" type="error" density="compact" class="mt-3">{{ grupoError }}</v-alert>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="error" variant="flat" :disabled="guardandoGrupo" @click="dialogGrupo = false">Cancelar</v-btn>
                <v-btn color="success" variant="flat" :loading="guardandoGrupo" :disabled="guardandoGrupo" @click="guardarGrupo">Guardar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- crear / editar menu -->
    <v-dialog v-model="dialogMenu" max-width="560px">
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h5">{{ editandoMenu ? 'Editar menú' : 'Nuevo menú' }}</span>
            </v-card-title>
            <v-card-text>
                <v-form ref="menuFormRef">
                    <v-row class="mt-1">
                        <v-col cols="12" v-if="!tienePadre">
                            <v-select
                                v-model="formMenu.menuGrupoId"
                                label="Grupo de menú"
                                :items="store.grupos"
                                item-title="grupo"
                                item-value="id"
                                :rules="[(v: number | null) => !!v || 'Selecciona un grupo.']"
                            />
                        </v-col>
                        <v-col cols="12" v-else>
                            <v-alert type="info" variant="tonal" density="compact"
                                >Hereda el grupo de su menú padre.</v-alert
                            >
                        </v-col>
                        <v-col cols="12">
                            <v-select
                                v-model="formMenu.menuPadreId"
                                label="Menú padre (opcional — vacío = primer nivel)"
                                :items="[{ id: null, etiqueta: 'Ninguno (primer nivel)' }, ...opcionesPadre]"
                                item-title="etiqueta"
                                item-value="id"
                                :disabled="!padreEditable"
                                :hint="padreEditable ? undefined : 'Este menú ya tiene submenús propios: no puede convertirse en submenú de otro.'"
                                :persistent-hint="!padreEditable"
                                clearable
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-text-field v-model="formMenu.menu" label="Etiqueta" :rules="[requeridoRule]" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="formMenu.icono" label="Ícono (opcional)" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="formMenu.ruta" label="Ruta (opcional)" placeholder="/configuracion/..." />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model="formMenu.orden"
                                type="number"
                                label="Orden"
                                hint="Entre hermanos del mismo grupo o padre. Vacío = al final."
                                persistent-hint
                            />
                        </v-col>
                    </v-row>
                </v-form>
                <v-alert v-if="menuError" type="error" density="compact" class="mt-3">{{ menuError }}</v-alert>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="error" variant="flat" :disabled="guardandoMenu" @click="dialogMenu = false">Cancelar</v-btn>
                <v-btn color="success" variant="flat" :loading="guardandoMenu" :disabled="guardandoMenu" @click="guardarMenu">Guardar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- roles asociados a un menú -->
    <v-dialog v-model="dialogRoles" max-width="640px">
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h5">Roles con acceso — {{ menuRoles?.etiqueta }}</span>
            </v-card-title>
            <v-card-text>
                <div v-if="rolesCargando" class="text-center py-6"><v-progress-circular indeterminate color="primary" /></div>
                <v-table v-else density="compact">
                    <thead>
                        <tr>
                            <th class="w-50">Rol (nivel 1)</th>
                            <th class="w-50">Roles hijos (nivel 2 y 3)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="padre in rolesAsociables" :key="padre.id">
                            <td class="align-top pt-2">
                                <v-checkbox
                                    :model-value="tieneRolAsociado(padre.id)"
                                    :label="padre.role"
                                    hide-details
                                    density="compact"
                                    class="font-weight-bold"
                                    :loading="roleCambiando.has(padre.id)"
                                    :disabled="roleCambiando.has(padre.id) || nivel1Bloqueado()"
                                    @update:model-value="toggleRolAsociado(padre)"
                                />
                            </td>
                            <td class="align-top pt-2">
                                <span v-if="padre.hijos.length === 0" class="text-caption text-medium-emphasis">Sin roles hijos</span>
                                <div
                                    v-for="hijo in padre.hijos"
                                    :key="hijo.id"
                                    :style="{ paddingLeft: `${hijo.profundidad * 24}px` }"
                                >
                                    <v-checkbox
                                        :model-value="tieneRolAsociado(hijo.id)"
                                        :label="hijo.role"
                                        hide-details
                                        density="compact"
                                        :loading="roleCambiando.has(hijo.id)"
                                        :disabled="roleCambiando.has(hijo.id) || nivelesHijoBloqueados()"
                                        @update:model-value="toggleRolAsociado(hijo)"
                                    />
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </v-table>
                <v-alert type="info" variant="tonal" density="compact" class="mt-3">
                    <template v-if="menuEsPadre"
                        >Este es un menú de primer nivel: solo se le pueden asociar roles de primer nivel (columna izquierda).</template
                    >
                    <template v-else
                        >Este es un submenú: se le pueden asociar roles de segundo o tercer nivel (columna derecha, el nieto aparece indentado bajo su padre).</template
                    >
                </v-alert>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="primary" variant="flat" @click="dialogRoles = false">Cerrar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- eliminar grupo -->
    <ConfirmDialog
        v-model="dialogEliminarGrupo"
        title="Eliminar grupo de menú"
        :text="`Vas a eliminar el grupo &quot;${grupoAEliminar?.grupo}&quot;. Se eliminarán también TODOS sus menús, submenús y las relaciones de esos menús con los roles asociados. Esta acción no se puede deshacer.`"
        :loading="eliminandoGrupo"
        @confirm="eliminarGrupoConfirmado"
    />

    <!-- eliminar menú -->
    <ConfirmDialog
        v-model="dialogEliminarMenu"
        title="Eliminar menú"
        :text="textoConfirmarEliminarMenu"
        :loading="eliminandoMenu"
        @confirm="eliminarMenuConfirmado"
    />

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top right" timeout="4000">
        {{ snackbar.text }}
    </v-snackbar>
</template>
