<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { useCasStore } from '@/stores/cas';
import { casService, type CasAdmin, type ClienteBusqueda, type SucursalDeCliente } from '@/services/casService';

const props = defineProps<{ modelValue: boolean; cas: CasAdmin | null }>();
const emit = defineEmits<{ 'update:modelValue': [boolean]; guardado: [string]; error: [unknown] }>();

const store = useCasStore();
const dialog = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit('update:modelValue', v)
});

const CARTA_CAMBIO_VENCE_DEFECTO = 365;

const form = reactive({
    nit: '',
    nombre: '',
    direccion: '',
    telefono: '',
    ciudadId: null as number | null,
    cartaCambioVence: CARTA_CAMBIO_VENCE_DEFECTO,
    estado: true
});

const busquedaCliente = ref('');
const clienteSeleccionado = ref<ClienteBusqueda | null>(null);
const buscandoClientes = ref(false);
const clientesEncontrados = ref<ClienteBusqueda[]>([]);

const sucursales = ref<SucursalDeCliente[]>([]);
const sucursalSeleccionada = ref<SucursalDeCliente | null>(null);
const cargandoSucursales = ref(false);

const formRef = ref();
const formError = ref('');
const guardando = ref(false);
/** Evita que el watch de clienteSeleccionado limpie la sucursal mientras se está precargando un CAS existente para editar. */
let precargando = false;

const requeridoRule = (v: string) => !!v?.trim() || 'Este campo es obligatorio.';
const nombreRules = computed(() => (sucursalSeleccionada.value ? [] : [requeridoRule]));

let debounce: ReturnType<typeof setTimeout> | undefined;
watch(busquedaCliente, (q) => {
    if (clienteSeleccionado.value && q === clienteSeleccionado.value.nombre) return;
    clearTimeout(debounce);
    debounce = setTimeout(async () => {
        if (!q || q.trim().length < 2) {
            clientesEncontrados.value = [];
            return;
        }
        buscandoClientes.value = true;
        try {
            clientesEncontrados.value = await casService.buscarClientes(q);
        } finally {
            buscandoClientes.value = false;
        }
    }, 350);
});

watch(clienteSeleccionado, async (cliente) => {
    if (precargando) return;
    sucursales.value = [];
    sucursalSeleccionada.value = null;
    if (!cliente) return;
    cargandoSucursales.value = true;
    try {
        sucursales.value = await casService.sucursalesDeCliente(cliente.id);
    } finally {
        cargandoSucursales.value = false;
    }
});

/**
 * Ciudad y NIT quedan bloqueados (los define la sucursal/cliente) al
 * seleccionar una sucursal. La ciudad se prellena con la de la sucursal
 * (de solo lectura, igual que el NIT se prellena con el del cliente) — no
 * se envía en el payload igual (ver guardar()), es solo para mostrarla.
 */
watch(sucursalSeleccionada, (sucursal) => {
    if (precargando) return;
    if (sucursal) {
        form.ciudadId = sucursal.ciudadId ?? null;
        form.nit = '';
    }
});

watch(
    () => props.modelValue,
    async (abierto) => {
        if (!abierto) return;
        formError.value = '';
        formRef.value?.resetValidation();

        if (!store.ciudades.length) {
            await store.cargarCiudades();
        }

        if (props.cas) {
            precargando = true;
            Object.assign(form, {
                nit: props.cas.nitPropio ?? '',
                nombre: props.cas.nombrePropio ?? '',
                direccion: props.cas.direccionPropio ?? '',
                telefono: props.cas.telefonoPropio ?? '',
                ciudadId: props.cas.ciudadId,
                cartaCambioVence: props.cas.cartaCambioVence,
                estado: props.cas.estado
            });
            if (props.cas.clienteId && props.cas.clienteSucursalId) {
                clienteSeleccionado.value = { id: props.cas.clienteId, numeroIdentidad: '', nombre: props.cas.clienteNombre ?? '' };
                busquedaCliente.value = props.cas.clienteNombre ?? '';
                cargandoSucursales.value = true;
                try {
                    sucursales.value = await casService.sucursalesDeCliente(props.cas.clienteId);
                    sucursalSeleccionada.value = sucursales.value.find((s) => s.id === props.cas!.clienteSucursalId) ?? null;
                    // El watch de sucursalSeleccionada no corre durante la precarga (ver flag `precargando`), así que la ciudad se fija a mano aquí.
                    form.ciudadId = sucursalSeleccionada.value?.ciudadId ?? null;
                } finally {
                    cargandoSucursales.value = false;
                }
            } else {
                clienteSeleccionado.value = null;
                busquedaCliente.value = '';
                sucursales.value = [];
                sucursalSeleccionada.value = null;
            }
            precargando = false;
        } else {
            Object.assign(form, {
                nit: '',
                nombre: '',
                direccion: '',
                telefono: '',
                ciudadId: null,
                cartaCambioVence: CARTA_CAMBIO_VENCE_DEFECTO,
                estado: true
            });
            clienteSeleccionado.value = null;
            busquedaCliente.value = '';
            sucursales.value = [];
            sucursalSeleccionada.value = null;
        }
    }
);

async function guardar() {
    const { valid } = await formRef.value.validate();
    if (!valid) return;

    guardando.value = true;
    formError.value = '';
    try {
        const conSucursal = !!sucursalSeleccionada.value;
        const payload = {
            clienteSucursalId: sucursalSeleccionada.value?.id ?? null,
            // Ciudad y NIT quedan bloqueados cuando hay sucursal (los define
            // el cliente/sucursal); nombre, dirección y teléfono siempre son
            // editables — permiten registrar datos propios del CAS aunque
            // exista sucursal, sin perder el fallback por COALESCE si se
            // dejan en blanco (ver CasService::aplicarDatos en el backend).
            ciudadId: conSucursal ? null : form.ciudadId,
            nit: conSucursal ? null : form.nit || null,
            nombre: form.nombre || null,
            direccion: form.direccion || null,
            telefono: form.telefono || null,
            cartaCambioVence: form.cartaCambioVence,
            estado: form.estado
        };

        if (props.cas) {
            await store.actualizar(props.cas.id, payload);
            emit('guardado', 'Centro de servicio actualizado correctamente.');
        } else {
            await store.crear(payload);
            emit('guardado', 'Centro de servicio creado correctamente.');
        }
    } catch (e) {
        formError.value = typeof e === 'string' ? e : 'No se pudo guardar el centro de servicio.';
        emit('error', e);
    } finally {
        guardando.value = false;
    }
}
</script>

<template>
    <v-dialog v-model="dialog" max-width="720px" scrollable>
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h5">{{ cas ? 'Editar centro de servicio' : 'Nuevo centro de servicio' }}</span>
            </v-card-title>
            <v-card-text>
                <v-form ref="formRef">
                    <v-row class="mt-1">
                        <v-col cols="12">
                            <v-autocomplete
                                v-model="clienteSeleccionado"
                                v-model:search="busquedaCliente"
                                :items="clientesEncontrados"
                                :loading="buscandoClientes"
                                :item-props="(c: ClienteBusqueda) => ({ title: c.nombre, subtitle: `NIT: ${c.numeroIdentidad}` })"
                                item-title="nombre"
                                return-object
                                label="Cliente (opcional)"
                                placeholder="Escribe al menos 2 caracteres para buscar"
                                prepend-inner-icon="mdi-domain"
                                hide-no-data
                                clearable
                                no-filter
                            />
                        </v-col>
                        <v-col cols="12" v-if="clienteSeleccionado">
                            <v-select
                                v-model="sucursalSeleccionada"
                                :items="sucursales"
                                :loading="cargandoSucursales"
                                item-title="nombre"
                                return-object
                                label="Sucursal (opcional)"
                                clearable
                                hint="Una sucursal solo puede estar asociada a un CAS."
                                persistent-hint
                            />
                        </v-col>

                        <v-col cols="12" v-if="sucursalSeleccionada">
                            <v-alert type="info" variant="tonal" density="compact">
                                Ciudad y NIT los define la sucursal seleccionada. Nombre, dirección y teléfono son
                                opcionales aquí — déjalos en blanco para usar los de la sucursal, o complétalos si el
                                CAS tiene sus propios datos.
                            </v-alert>
                        </v-col>

                        <v-col cols="12" sm="6">
                            <v-select
                                v-model="form.ciudadId"
                                :items="store.ciudades"
                                item-title="nombre"
                                item-value="id"
                                label="Ciudad"
                                :disabled="!!sucursalSeleccionada"
                                clearable
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field
                                :model-value="sucursalSeleccionada ? clienteSeleccionado?.numeroIdentidad : form.nit"
                                @update:model-value="(v: string) => (form.nit = v)"
                                label="NIT"
                                :disabled="!!sucursalSeleccionada"
                            />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="form.nombre" label="Nombre" :rules="nombreRules" />
                        </v-col>
                        <v-col cols="12" sm="6">
                            <v-text-field v-model="form.telefono" label="Teléfono" />
                        </v-col>
                        <v-col cols="12">
                            <v-text-field v-model="form.direccion" label="Dirección" />
                        </v-col>

                        <v-col cols="12" sm="6">
                            <v-text-field
                                v-model.number="form.cartaCambioVence"
                                type="number"
                                min="1"
                                label="Días para vencer carta de cambio"
                                hint="Por defecto 365 días"
                                persistent-hint
                            />
                        </v-col>
                        <v-col cols="12" sm="6" v-if="cas">
                            <v-switch v-model="form.estado" color="success" label="Activo" hide-details density="compact" />
                        </v-col>
                    </v-row>
                </v-form>
                <v-alert v-if="formError" type="error" density="compact" class="mt-3">{{ formError }}</v-alert>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="error" variant="flat" :disabled="guardando" @click="dialog = false">Cancelar</v-btn>
                <v-btn color="success" variant="flat" :loading="guardando" :disabled="guardando" @click="guardar">Guardar</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
