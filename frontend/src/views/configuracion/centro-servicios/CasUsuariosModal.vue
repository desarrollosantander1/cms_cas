<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useCasStore } from '@/stores/cas';
import { casService, type CasAdmin, type CasAsociado, type CasUsuarioAsociado, type CasUsuarioRow } from '@/services/casService';
import { passwordFuerteRules, passwordFuerteRulesOpcional } from '@/utils/helpers/passwordRules';

const props = defineProps<{ modelValue: boolean; cas: CasAdmin | null }>();
const emit = defineEmits<{ 'update:modelValue': [boolean]; notificar: [color: 'success' | 'error', texto: string] }>();

const store = useCasStore();
const dialog = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
});

function mensajeError(e: unknown, fallback: string): string {
    return typeof e === 'string' ? e : fallback;
}

// --- listado de usuarios ya asociados a este CAS ---
const listado = ref<CasUsuarioRow[]>([]);
const cargandoListado = ref(false);
const tabListado = ref<'activos' | 'inactivos'>('activos');
const filtroListado = ref<string | null>('');

async function cargarListado() {
    if (!props.cas) return;
    cargandoListado.value = true;
    try {
        listado.value = await store.usuariosDeCas(props.cas.id);
    } finally {
        cargandoListado.value = false;
    }
}

const listadoFiltrado = computed(() => {
    // v-text-field con `clearable` pone el v-model en null (no "") al limpiar
    // con el ícono — sin este fallback, .trim() sobre null rompe el computed.
    const q = (filtroListado.value ?? '').trim().toLowerCase();
    if (!q) return listado.value;
    return listado.value.filter((r) => [r.usuario, r.documento, r.nombre].some((campo) => (campo ?? '').toLowerCase().includes(q)));
});
const activos = computed(() => listadoFiltrado.value.filter((r) => r.estado));
const inactivos = computed(() => listadoFiltrado.value.filter((r) => !r.estado));
const listaActual = computed(() => (tabListado.value === 'activos' ? activos.value : inactivos.value));

// --- buscar por documento / asociar ---
const documento = ref('');
const buscando = ref(false);
const yaBuscado = ref(false);
const usuarioEncontrado = ref<CasUsuarioAsociado | null>(null);
const casAsociados = ref<CasAsociado[]>([]);
const mostrarPassword = ref(false);
const mostrarPasswordEdicion = ref(false);

const form = reactive({ nombre: '', apellido: '', telefono: '', usuario: '', email: '', password: '' });
const formRef = ref();
const formError = ref('');
const asociando = ref(false);

const requeridoRule = (v: string) => !!v?.trim() || 'Este campo es obligatorio.';
const emailRules = [requeridoRule, (v: string) => /.+@.+\..+/.test(v) || 'Ingresa un correo válido.'];
const passwordRules = computed(() => (usuarioEncontrado.value ? passwordFuerteRulesOpcional : passwordFuerteRules));

async function buscar() {
    if (!documento.value.trim()) return;
    buscando.value = true;
    formError.value = '';
    try {
        const resultado = await casService.buscarPorDocumento(documento.value.trim());
        usuarioEncontrado.value = resultado.usuario;
        casAsociados.value = resultado.casAsociados;
        yaBuscado.value = true;
        if (resultado.usuario) {
            Object.assign(form, {
                nombre: resultado.usuario.nombre ?? '',
                apellido: resultado.usuario.apellido ?? '',
                telefono: resultado.usuario.telefono ?? '',
                usuario: resultado.usuario.usuario,
                email: resultado.usuario.email,
                password: ''
            });
        } else {
            Object.assign(form, { nombre: '', apellido: '', telefono: '', usuario: '', email: '', password: '' });
        }
        formRef.value?.resetValidation();
    } catch (e) {
        emit('notificar', 'error', mensajeError(e, 'No se pudo buscar el usuario.'));
    } finally {
        buscando.value = false;
    }
}

const yaAsociadoAEsteCas = computed(() => !!props.cas && casAsociados.value.some((c) => c.casId === props.cas!.id));

async function asociar() {
    const { valid } = await formRef.value.validate();
    if (!valid || !props.cas) return;

    asociando.value = true;
    formError.value = '';
    try {
        const payload = usuarioEncontrado.value
            ? { documento: documento.value.trim(), password: form.password || undefined }
            : {
                  documento: documento.value.trim(),
                  nombre: form.nombre,
                  apellido: form.apellido,
                  telefono: form.telefono || null,
                  usuario: form.usuario,
                  email: form.email,
                  password: form.password
              };
        await store.asociarUsuario(props.cas.id, payload);
        emit('notificar', 'success', 'Usuario asociado correctamente.');
        resetBusqueda();
        await cargarListado();
    } catch (e) {
        formError.value = mensajeError(e, 'No se pudo asociar el usuario.');
    } finally {
        asociando.value = false;
    }
}

function resetBusqueda() {
    documento.value = '';
    yaBuscado.value = false;
    usuarioEncontrado.value = null;
    casAsociados.value = [];
    mostrarPassword.value = false;
    Object.assign(form, { nombre: '', apellido: '', telefono: '', usuario: '', email: '', password: '' });
    formRef.value?.resetValidation();
}

// --- editar un usuario ya asociado (columna "editar" del listado) ---
const dialogEditar = ref(false);
const edicion = reactive({ usuarioId: 0, documento: '', nombre: '', apellido: '', usuario: '', telefono: '', email: '', password: '', estado: true });
const editarFormRef = ref();
const editando = ref(false);
const editarError = ref('');

function abrirEdicion(row: CasUsuarioRow) {
    Object.assign(edicion, {
        usuarioId: row.usuarioId,
        documento: row.documento ?? '',
        nombre: '',
        apellido: '',
        usuario: row.usuario,
        telefono: '',
        email: '',
        password: '',
        estado: row.estado
    });
    editarError.value = '';
    mostrarPasswordEdicion.value = false;
    casService
        .buscarPorDocumento(row.documento ?? '')
        .then((r) => {
            if (r.usuario) {
                Object.assign(edicion, {
                    documento: r.usuario.documento ?? '',
                    nombre: r.usuario.nombre ?? '',
                    apellido: r.usuario.apellido ?? '',
                    usuario: r.usuario.usuario,
                    telefono: r.usuario.telefono ?? '',
                    email: r.usuario.email
                });
            }
        })
        .catch(() => undefined);
    dialogEditar.value = true;
}

async function guardarEdicion() {
    const { valid } = await editarFormRef.value.validate();
    if (!valid || !props.cas) return;

    editando.value = true;
    editarError.value = '';
    try {
        await casService.actualizarUsuario(props.cas.id, edicion.usuarioId, {
            documento: edicion.documento,
            nombre: edicion.nombre,
            apellido: edicion.apellido,
            usuario: edicion.usuario,
            telefono: edicion.telefono || null,
            email: edicion.email,
            password: edicion.password || undefined,
            estado: edicion.estado
        });
        emit('notificar', 'success', 'Usuario actualizado correctamente.');
        dialogEditar.value = false;
        await cargarListado();
    } catch (e) {
        editarError.value = mensajeError(e, 'No se pudo actualizar el usuario.');
    } finally {
        editando.value = false;
    }
}

watch(
    () => props.modelValue,
    (abierto) => {
        if (abierto) {
            resetBusqueda();
            tabListado.value = 'activos';
            filtroListado.value = '';
            cargarListado();
        }
    }
);
</script>

<template>
    <v-dialog v-model="dialog" max-width="820px" scrollable>
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h5">Usuarios de {{ cas?.nombre }}</span>
            </v-card-title>
            <v-card-text>
                <!--
                    d-flex + align-center (no v-row/v-col con hint bajo el campo):
                    el hint de "Documento" le sumaba altura extra solo a esa
                    columna, así que align="end" del v-row dejaba el botón
                    pegado al fondo de esa altura extra en vez de a la altura
                    del campo — quedaba visualmente desfasado del input.
                    hide-details + un caption aparte debajo evita ese desnivel.
                -->
                <div class="d-flex flex-wrap align-center ga-3 mt-1">
                    <v-text-field
                        v-model="documento"
                        label="Documento"
                        hide-details
                        style="max-width: 280px"
                        @keyup.enter="buscar"
                    />
                    <v-btn color="primary" :loading="buscando" :disabled="!documento.trim()" @click="buscar">
                        <v-icon start>mdi-magnify</v-icon>Buscar
                    </v-btn>
                </div>
                <div class="text-caption text-medium-emphasis mt-1">Busca primero por documento para saber si el usuario ya existe.</div>

                <template v-if="yaBuscado">
                    <v-alert v-if="usuarioEncontrado" type="info" variant="tonal" density="compact" class="mb-3">
                        El usuario ya existe — no se creará un registro nuevo, solo se asociará (o se actualizará su contraseña si escribes una nueva).
                    </v-alert>
                    <v-alert v-else type="success" variant="tonal" density="compact" class="mb-3">
                        No existe un usuario con ese documento — se creará uno nuevo al asociarlo.
                    </v-alert>

                    <v-table v-if="casAsociados.length > 0" density="compact" class="mb-3">
                        <thead>
                            <tr>
                                <th>Ya asociado a estos CAS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ca in casAsociados" :key="ca.casId">
                                <td>{{ ca.casNombre }}</td>
                            </tr>
                        </tbody>
                    </v-table>

                    <v-alert v-if="yaAsociadoAEsteCas" type="warning" variant="tonal" density="compact" class="mb-3">
                        Este usuario ya está asociado a este mismo CAS.
                    </v-alert>

                    <v-form v-else ref="formRef">
                        <v-row>
                            <v-col cols="12" sm="6">
                                <v-text-field v-model="form.nombre" label="Nombre" :rules="[requeridoRule]" :disabled="!!usuarioEncontrado" />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field v-model="form.apellido" label="Apellido" :rules="[requeridoRule]" :disabled="!!usuarioEncontrado" />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field v-model="form.usuario" label="Usuario" :rules="[requeridoRule]" :disabled="!!usuarioEncontrado" />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field v-model="form.email" label="Email" :rules="emailRules" :disabled="!!usuarioEncontrado" />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field v-model="form.telefono" label="Teléfono" :disabled="!!usuarioEncontrado" />
                            </v-col>
                            <v-col cols="12" sm="6">
                                <v-text-field
                                    v-model="form.password"
                                    :label="usuarioEncontrado ? 'Nueva contraseña' : 'Contraseña'"
                                    :hint="
                                        usuarioEncontrado
                                            ? 'Dejar en blanco para no modificarla'
                                            : 'Mínimo 8 caracteres, mayúscula, minúscula, número y carácter especial'
                                    "
                                    persistent-hint
                                    :rules="passwordRules"
                                    :type="mostrarPassword ? 'text' : 'password'"
                                    :append-inner-icon="mostrarPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                    @click:append-inner="mostrarPassword = !mostrarPassword"
                                />
                            </v-col>
                        </v-row>
                        <v-alert v-if="formError" type="error" density="compact" class="mt-2">{{ formError }}</v-alert>
                        <div class="d-flex justify-end mt-2">
                            <v-btn color="success" variant="flat" :loading="asociando" @click="asociar">
                                <v-icon start>mdi-account-plus</v-icon>Asociar al CAS
                            </v-btn>
                        </div>
                    </v-form>
                </template>

                <v-divider class="my-4" />

                <v-tooltip :text="`CAS id: ${cas?.id ?? '—'}`">
                    <template v-slot:activator="{ props: tprops }">
                        <div v-bind="tprops" class="text-subtitle-1 font-weight-semibold mb-2" style="width: fit-content">
                            {{ cas?.nit ?? '—' }} - {{ cas?.nombre ?? '—' }}
                        </div>
                    </template>
                </v-tooltip>

                <v-text-field
                    v-model="filtroListado"
                    label="Buscar usuario, documento o nombre"
                    density="compact"
                    variant="outlined"
                    hide-details
                    clearable
                    class="mb-3"
                    style="max-width: 320px"
                />

                <v-tabs v-model="tabListado" color="primary" density="compact">
                    <v-tab value="activos">Activos ({{ activos.length }})</v-tab>
                    <v-tab value="inactivos">Inactivos ({{ inactivos.length }})</v-tab>
                </v-tabs>

                <v-table density="compact" class="mt-3">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Documento</th>
                            <th>Nombre</th>
                            <th>Fecha creación</th>
                            <th>Estado</th>
                            <th>Editar</th>
                        </tr>
                    </thead>
                    <tbody v-if="cargandoListado">
                        <tr v-for="n in 3" :key="`skeleton-${n}`">
                            <td v-for="c in 6" :key="c"><v-skeleton-loader type="text" /></td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr v-for="row in listaActual" :key="row.id">
                            <td>{{ row.usuario }}</td>
                            <td>{{ row.documento ?? '—' }}</td>
                            <td>{{ row.nombre || '—' }}</td>
                            <td>{{ new Date(row.creadoEn).toLocaleDateString() }}</td>
                            <td><v-chip :color="row.estado ? 'success' : 'error'" size="small">{{ row.estado ? 'Activo' : 'Inactivo' }}</v-chip></td>
                            <td>
                                <v-btn icon flat size="small" @click="abrirEdicion(row)"><v-icon size="18">mdi-pencil</v-icon></v-btn>
                            </td>
                        </tr>
                        <tr v-if="listaActual.length === 0">
                            <td colspan="6" class="text-center text-medium-emphasis py-4">Sin usuarios {{ tabListado === 'activos' ? 'activos' : 'inactivos' }}.</td>
                        </tr>
                    </tbody>
                </v-table>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="secondary" variant="flat" @click="dialog = false">Cerrar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <!-- editar usuario ya asociado -->
    <v-dialog v-model="dialogEditar" max-width="560px">
        <v-card>
            <v-card-title class="pa-4 bg-secondary"><span class="text-h5">Editar usuario</span></v-card-title>
            <v-card-text>
                <v-form ref="editarFormRef" class="mt-1">
                    <div class="text-overline text-medium-emphasis">Datos personales</div>
                    <v-row class="mt-0">
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="edicion.documento" label="Documento" :rules="[requeridoRule]" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="edicion.nombre" label="Nombre" :rules="[requeridoRule]" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="edicion.apellido" label="Apellido" :rules="[requeridoRule]" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="edicion.telefono" label="Teléfono" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="edicion.email" label="Email" :rules="emailRules" />
                        </v-col>
                    </v-row>

                    <div class="text-overline text-medium-emphasis mt-2">Acceso</div>
                    <v-row class="mt-0" align="center">
                        <v-col cols="12" sm="7">
                            <v-text-field v-model="edicion.usuario" label="Usuario" :rules="[requeridoRule]" />
                        </v-col>
                        <v-col cols="12" sm="5">
                            <v-switch v-model="edicion.estado" color="success" label="Activo" hide-details density="compact" />
                        </v-col>
                        <v-col cols="12">
                            <v-text-field
                                v-model="edicion.password"
                                label="Nueva contraseña"
                                hint="Dejar en blanco para no modificarla"
                                persistent-hint
                                :rules="passwordFuerteRulesOpcional"
                                :type="mostrarPasswordEdicion ? 'text' : 'password'"
                                :append-inner-icon="mostrarPasswordEdicion ? 'mdi-eye-off' : 'mdi-eye'"
                                @click:append-inner="mostrarPasswordEdicion = !mostrarPasswordEdicion"
                            />
                        </v-col>
                    </v-row>
                </v-form>
                <v-alert v-if="editarError" type="error" density="compact" class="mt-2">{{ editarError }}</v-alert>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="error" variant="flat" :disabled="editando" @click="dialogEditar = false">Cancelar</v-btn>
                <v-btn color="success" variant="flat" :loading="editando" @click="guardarEdicion">Guardar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
