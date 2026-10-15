<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useCasStore } from '@/stores/cas';
import type { CasAdmin } from '@/services/casService';
import CasFormModal from './CasFormModal.vue';
import CasUsuariosModal from './CasUsuariosModal.vue';

const store = useCasStore();
const search = ref('');

const filteredItems = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return store.casList;
    return store.casList.filter((c) => [c.nit, c.nombre, c.direccion, c.telefono].some((campo) => (campo ?? '').toLowerCase().includes(q)));
});

// --- paginación (mismo patrón que UsersAdmin.vue) ---
const pagina = ref(1);
const itemsPorPagina = ref(10);
const totalPaginas = computed(() => Math.max(1, Math.ceil(filteredItems.value.length / itemsPorPagina.value)));
const itemsPaginados = computed(() => {
    const inicio = (pagina.value - 1) * itemsPorPagina.value;
    return filteredItems.value.slice(inicio, inicio + itemsPorPagina.value);
});

const snackbar = reactive({ show: false, color: 'success', text: '' });
function notificar(color: 'success' | 'error', text: string) {
    snackbar.color = color;
    snackbar.text = text;
    snackbar.show = true;
}
function mensajeError(e: unknown, fallback: string): string {
    return typeof e === 'string' ? e : fallback;
}

onMounted(async () => {
    try {
        await store.cargarCas();
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudieron cargar los centros de servicio.'));
    }
});

// --- crear / editar CAS ---
const dialogForm = ref(false);
const editando = ref<CasAdmin | null>(null);

function nuevo() {
    editando.value = null;
    dialogForm.value = true;
}
function editar(cas: CasAdmin) {
    editando.value = cas;
    dialogForm.value = true;
}
function alGuardar(mensaje: string) {
    notificar('success', mensaje);
    dialogForm.value = false;
}
function alFallar(e: unknown) {
    notificar('error', mensajeError(e, 'No se pudo guardar el centro de servicio.'));
}

// --- modal de usuarios asociados ---
const dialogUsuarios = ref(false);
const casUsuarios = ref<CasAdmin | null>(null);

function verUsuarios(cas: CasAdmin) {
    casUsuarios.value = cas;
    dialogUsuarios.value = true;
}
</script>

<template>
    <!--
        pt-3: este tab es el contenido de un v-window-item (tabs CAS/Usuarios
        de CentroServiciosAdmin.vue). v-window usa overflow:hidden para
        animar el cambio de pestaña y su borde superior queda pegado al top
        del primer hijo — el label flotante del campo de búsqueda sobresale
        por encima del borde del campo (notch de un v-field outlined) y sin
        este espacio queda recortado por ese overflow:hidden, no por ningún
        estilo del campo en sí (mismo bug ya resuelto en PeriodoToolbar.vue).
    -->
    <v-row class="pt-3">
        <v-col cols="12" lg="4" md="6">
            <v-text-field density="compact" v-model="search" label="Buscar por NIT, nombre, dirección o teléfono" hide-details variant="outlined" />
        </v-col>
        <v-col cols="12" lg="8" md="6" class="text-right">
            <v-btn v-can="'ROLE_CAS_ADMIN'" color="primary" flat class="ml-auto" @click="nuevo">
                <v-icon class="mr-2">mdi-store-plus-outline</v-icon>Nuevo CAS
            </v-btn>
        </v-col>
    </v-row>

    <v-table class="mt-5">
        <thead>
            <tr>
                <th class="text-subtitle-1 font-weight-semibold">NIT</th>
                <th class="text-subtitle-1 font-weight-semibold">ID / Nombre</th>
                <th class="text-subtitle-1 font-weight-semibold">Dirección</th>
                <th class="text-subtitle-1 font-weight-semibold">Teléfono</th>
                <th class="text-subtitle-1 font-weight-semibold">Estado</th>
                <th class="text-subtitle-1 font-weight-semibold">Creado</th>
                <th class="text-subtitle-1 font-weight-semibold">Acciones</th>
            </tr>
        </thead>
        <tbody v-if="store.loading">
            <tr v-for="n in 5" :key="`skeleton-${n}`">
                <td v-for="c in 7" :key="c"><v-skeleton-loader type="text" /></td>
            </tr>
        </tbody>
        <tbody v-else>
            <tr v-for="item in itemsPaginados" :key="item.id">
                <td class="text-subtitle-1">{{ item.nit ?? '—' }}</td>
                <td class="text-subtitle-1">{{ item.id }} - {{ item.nombre ?? '—' }}</td>
                <td class="text-subtitle-1">{{ item.direccion ?? '—' }}</td>
                <td class="text-subtitle-1">{{ item.telefono ?? '—' }}</td>
                <td>
                    <v-chip :color="item.estado ? 'success' : 'error'" size="small">{{ item.estado ? 'Activo' : 'Inactivo' }}</v-chip>
                </td>
                <td class="text-subtitle-1">{{ new Date(item.creadoEn).toLocaleDateString() }}</td>
                <td>
                    <div class="d-flex align-center">
                        <v-tooltip text="Usuarios asociados">
                            <template v-slot:activator="{ props }">
                                <v-btn icon flat v-bind="props" @click="verUsuarios(item)"><v-icon>mdi-account-group-outline</v-icon></v-btn>
                            </template>
                        </v-tooltip>
                        <v-tooltip text="Editar">
                            <template v-slot:activator="{ props }">
                                <v-btn v-can="'ROLE_CAS_ADMIN'" icon flat v-bind="props" @click="editar(item)"
                                    ><v-icon>mdi-pencil</v-icon></v-btn
                                >
                            </template>
                        </v-tooltip>
                    </div>
                </td>
            </tr>
            <tr v-if="filteredItems.length === 0">
                <td colspan="7" class="text-center text-medium-emphasis py-6">Sin resultados.</td>
            </tr>
        </tbody>
    </v-table>

    <div v-if="filteredItems.length > 0" class="d-flex justify-center mt-3">
        <v-pagination v-model="pagina" :length="totalPaginas" :total-visible="7"></v-pagination>
    </div>

    <CasFormModal v-model="dialogForm" :cas="editando" @guardado="alGuardar" @error="alFallar" />
    <CasUsuariosModal v-model="dialogUsuarios" :cas="casUsuarios" @notificar="(c, t) => notificar(c, t)" />

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top right" timeout="4000">
        {{ snackbar.text }}
    </v-snackbar>
</template>
