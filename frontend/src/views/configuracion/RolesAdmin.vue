<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import RoleTreeNode from './roles/RoleTreeNode.vue';
import { useRolesStore } from '@/stores/roles';
import type { RolePermisoAdmin } from '@/services/rolesService';

/** Nodo de árbol armado en memoria desde la lista plana (rolePadreId) — ver `arbol` más abajo. */
export interface RoleNodo extends RolePermisoAdmin {
    hijos: RoleNodo[];
}

const NIVEL_MAXIMO = 3;

const page = { title: 'Gestión de roles' };
const breadcrumbs = [
    { text: 'Configuración', disabled: true, href: '#' },
    { text: 'Administración', disabled: true, href: '#' },
    { text: 'Gestión de roles', disabled: true, href: '#' },
];

const store = useRolesStore();
const snackbar = reactive({ show: false, color: 'success', text: '' });

function notificar(color: 'success' | 'error', text: string) {
    snackbar.color = color;
    snackbar.text = text;
    snackbar.show = true;
}

function mensajeError(e: unknown, fallback: string): string {
    return typeof e === 'string' ? e : fallback;
}

onMounted(() => store.cargar());

/** Árbol de máximo 3 niveles armado en memoria — no hace falta un endpoint aparte para esto. */
const arbol = computed<RoleNodo[]>(() => {
    const porPadre = new Map<number | null, RolePermisoAdmin[]>();
    for (const rol of store.roles) {
        const clave = rol.rolePadreId;
        if (!porPadre.has(clave)) porPadre.set(clave, []);
        porPadre.get(clave)!.push(rol);
    }

    function construir(padreId: number | null): RoleNodo[] {
        return (porPadre.get(padreId) ?? []).map((rol) => ({ ...rol, hijos: construir(rol.id) }));
    }

    return construir(null);
});

function idsDescendientes(rol: RolePermisoAdmin): Set<number> {
    const ids = new Set<number>();
    function recorrer(padreId: number) {
        for (const hijo of store.roles.filter((r) => r.rolePadreId === padreId)) {
            ids.add(hijo.id);
            recorrer(hijo.id);
        }
    }
    recorrer(rol.id);
    return ids;
}

/** Niveles que cuelgan hoy debajo de $rol (0 si no tiene hijos) — mismo cálculo que RolePermisoService::alturaSubarbol() en el backend. */
function alturaSubarbol(rol: RolePermisoAdmin): number {
    const hijos = store.roles.filter((r) => r.rolePadreId === rol.id);
    if (hijos.length === 0) return 0;
    return 1 + Math.max(...hijos.map(alturaSubarbol));
}

const dialog = ref(false);
const editando = ref<RolePermisoAdmin | null>(null);
const form = reactive<{ role: string; rolePadreId: number | null }>({ role: '', rolePadreId: null });
const formRef = ref();
const formError = ref('');
const guardando = ref(false);
const cambiandoEstado = ref<Set<number>>(new Set());
const requeridoRule = (v: string) => !!v?.trim() || 'Este campo es obligatorio.';

/**
 * Un rol solo puede elegirse como padre si, sumando el nivel resultante más
 * la altura del subárbol propio (si se está editando uno con hijos), no se
 * pasa del tope de 3 niveles — evita ofrecer opciones que el backend
 * (RolePermisoService::validarNivelMaximo) igual rechazaría.
 */
const opcionesPadre = computed(() => {
    const excluidos = editando.value ? new Set([editando.value.id, ...idsDescendientes(editando.value)]) : new Set<number>();
    const alturaPropia = editando.value ? alturaSubarbol(editando.value) : 0;
    return store.roles.filter((r) => !excluidos.has(r.id) && r.nivel + 1 + alturaPropia <= NIVEL_MAXIMO);
});

function nuevo(padre?: RoleNodo) {
    editando.value = null;
    Object.assign(form, { role: '', rolePadreId: padre ? padre.id : null });
    formError.value = '';
    formRef.value?.resetValidation();
    dialog.value = true;
}

function editar(rol: RoleNodo) {
    editando.value = rol;
    Object.assign(form, { role: rol.role, rolePadreId: rol.rolePadreId });
    formError.value = '';
    formRef.value?.resetValidation();
    dialog.value = true;
}

async function guardar() {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    guardando.value = true;
    formError.value = '';
    try {
        const payload = { role: form.role, rolePadreId: form.rolePadreId };
        if (editando.value) {
            await store.actualizar(editando.value.id, payload);
        } else {
            await store.crear(payload);
        }
        notificar('success', editando.value ? 'Rol actualizado.' : 'Rol creado.');
        dialog.value = false;
    } catch (e) {
        formError.value = mensajeError(e, 'No se pudo guardar el rol.');
        notificar('error', formError.value);
    } finally {
        guardando.value = false;
    }
}

async function toggleEstado(rol: RoleNodo) {
    if (cambiandoEstado.value.has(rol.id)) return;
    cambiandoEstado.value.add(rol.id);
    try {
        if (rol.estado) {
            await store.desactivar(rol.id);
        } else {
            await store.activar(rol.id);
        }
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo cambiar el estado del rol.'));
    } finally {
        cambiandoEstado.value.delete(rol.id);
    }
}
</script>

<template>
    <BaseBreadcrumb :title="page.title" :breadcrumbs="breadcrumbs"></BaseBreadcrumb>

    <v-card elevation="10">
        <v-card-text>
            <div class="d-flex justify-end mb-3">
                <v-btn v-can="'ROLE_GESTIONAR_ROLES'" color="primary" flat @click="nuevo()">
                    <v-icon class="mr-2">mdi-plus</v-icon>Nuevo rol
                </v-btn>
            </div>

            <div v-if="store.loading" class="text-center py-6"><v-progress-circular indeterminate color="primary" /></div>

            <v-table v-else density="compact">
                <thead>
                    <tr>
                        <th class="text-subtitle-2 font-weight-semibold">Rol</th>
                        <th class="text-subtitle-2 font-weight-semibold">Nivel</th>
                        <th class="text-subtitle-2 font-weight-semibold">Estado</th>
                        <th class="text-subtitle-2 font-weight-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <RoleTreeNode
                        v-for="nodo in arbol"
                        :key="nodo.id"
                        :node="nodo"
                        :cambiando-estado="cambiandoEstado"
                        @editar="editar"
                        @agregar-hijo="nuevo"
                        @toggle-estado="toggleEstado"
                    />
                    <tr v-if="arbol.length === 0">
                        <td colspan="4" class="text-center text-medium-emphasis py-6">Sin roles todavía.</td>
                    </tr>
                </tbody>
            </v-table>
        </v-card-text>
    </v-card>

    <!-- crear / editar rol -->
    <v-dialog v-model="dialog" max-width="480px">
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h5">{{ editando ? 'Editar rol' : 'Nuevo rol' }}</span>
            </v-card-title>
            <v-card-text>
                <v-form ref="formRef">
                    <v-row class="mt-1">
                        <v-col cols="12">
                            <v-text-field
                                v-model="form.role"
                                label="Nombre del rol (ej. ROLE_USUARIO)"
                                :rules="[requeridoRule]"
                                :readonly="!!editando"
                                :hint="editando ? 'El nombre no se puede cambiar una vez creado: el código lo referencia literalmente (permisos de rutas y menús).' : undefined"
                                :persistent-hint="!!editando"
                            />
                        </v-col>
                        <v-col cols="12">
                            <v-select
                                v-model="form.rolePadreId"
                                label="Rol padre (opcional — vacío = primer nivel)"
                                :items="[{ id: null, role: 'Ninguno (primer nivel)' }, ...opcionesPadre]"
                                item-title="role"
                                item-value="id"
                                clearable
                            />
                        </v-col>
                    </v-row>
                </v-form>
                <v-alert type="info" variant="tonal" density="compact" class="mt-3">
                    La jerarquía se limita a tres niveles: rol padre → rol hijo → rol sub-hijo.
                </v-alert>
                <v-alert v-if="formError" type="error" density="compact" class="mt-3">{{ formError }}</v-alert>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="error" variant="flat" :disabled="guardando" @click="dialog = false">Cancelar</v-btn>
                <v-btn color="success" variant="flat" :loading="guardando" :disabled="guardando" @click="guardar">Guardar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top right" timeout="4000">
        {{ snackbar.text }}
    </v-snackbar>
</template>
