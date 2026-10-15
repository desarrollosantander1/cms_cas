<script setup lang="ts">
import { computed, reactive, ref, watch, onBeforeUnmount, onMounted } from 'vue';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import PdfPreviewDialog from './generador-cartas/PdfPreviewDialog.vue';
import TasaInteresDialog from './generador-cartas/TasaInteresDialog.vue';
import { useGeneradorCartasStore } from '@/stores/cartera/generadorCartasStore';
import { usePermissionsStore } from '@/stores/permissions';
import { formatCurrency, formatFecha } from '@/utils/helpers/format';
import { CARTAS, type ClienteBusqueda, type GenerarPdfPayload, type TipoCartaJuridica } from '@/types/generadorCartas';
import type { ArchivoDescargado } from '@/utils/helpers/fetch-wrapper';

const page = { title: 'Generador de cartas' };
const breadcrumbs = [
    { text: 'Cartera', disabled: true, href: '#' },
    { text: 'Gestión cartera', disabled: true, href: '#' },
    { text: 'Generador de cartas', disabled: true, href: '#' }
];

const store = useGeneradorCartasStore();
const permissions = usePermissionsStore();
const snackbar = reactive({ show: false, color: 'success', text: '' });

const puedeEditarTasa = computed(() => permissions.can('ROLE_GENERADOR_CARTAS_TASA'));
const dialogTasa = ref(false);

onMounted(() => {
    store.cargarTasa().catch((e) => notificar('error', mensajeError(e, 'No se pudo cargar la tasa de mora.')));
});

function guardarTasa(tasaMensual: number) {
    store.establecerTasaSesion(tasaMensual);
    notificar('success', `Tasa de mora ajustada a ${tasaMensual}% mensual para esta sesión.`);
    dialogTasa.value = false;
}

function notificar(color: 'success' | 'error', text: string) {
    snackbar.color = color;
    snackbar.text = text;
    snackbar.show = true;
}

function mensajeError(e: unknown, fallback: string): string {
    return typeof e === 'string' ? e : fallback;
}

const busqueda = ref('');
/** Proxy sobre store.clienteSeleccionado: vive en el store (no en un ref local) para que sobreviva al navegar a otro módulo y volver, igual que store.facturas. */
const clienteSeleccionado = computed<ClienteBusqueda | null>({
    get: () => store.clienteSeleccionado,
    set: (cliente) => {
        store.clienteSeleccionado = cliente;
    }
});
const seleccionadas = ref<string[]>([]);
const paramsDays = ref<number | null>(null);

let debounce: ReturnType<typeof setTimeout> | undefined;
watch(busqueda, (q) => {
    if (clienteSeleccionado.value && q === clienteSeleccionado.value.nombre) return;
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        store.buscarClientes(q ?? '').catch((e) => notificar('error', mensajeError(e, 'No se pudieron buscar clientes.')));
    }, 350);
});

watch(clienteSeleccionado, async (cliente) => {
    seleccionadas.value = [];
    store.facturas = [];
    if (!cliente) return;
    try {
        await store.buscarFacturas(cliente.nit);
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudieron cargar las facturas del cliente.'));
    }
});

const headers = [
    { title: 'Factura', key: 'comprobante' },
    { title: 'F. Factura', key: 'fechaFactura' },
    { title: 'F. Vencimiento', key: 'fechaVencimiento' },
    { title: 'Valor inicial', key: 'valorInicial', align: 'end' as const },
    { title: 'Saldo', key: 'saldo', align: 'end' as const },
    { title: 'Días mora', key: 'diasMora', align: 'end' as const }
];

const facturasSeleccionadas = computed(() => store.facturas.filter((f) => seleccionadas.value.includes(f.comprobante)));
const totalSeleccionado = computed(() => facturasSeleccionadas.value.reduce((acc, f) => acc + f.saldo, 0));
const sinSeleccion = computed(() => seleccionadas.value.length === 0);
/** Correo de la sucursal de la 1ª factura seleccionada — prellena el destinatario. */
const correoSugerido = computed(() => facturasSeleccionadas.value.find((f) => f.correo)?.correo ?? null);

// --- previsualización ---
const preview = ref(false);
const previewSrc = ref<string | null>(null);
const previewArchivo = ref<ArchivoDescargado | null>(null);
const previewTitulo = ref('');
/** Payload que generó la carta en pantalla — se reusa para "Enviar". */
const previewPayload = ref<GenerarPdfPayload | null>(null);

function revocarPreview() {
    if (previewSrc.value) URL.revokeObjectURL(previewSrc.value);
    previewSrc.value = null;
}
onBeforeUnmount(revocarPreview);
watch(preview, (abierto) => {
    if (!abierto) revocarPreview();
});

async function generar(tipo: TipoCartaJuridica) {
    if (!clienteSeleccionado.value || sinSeleccion.value) return;
    const meta = CARTAS.find((c) => c.tipo === tipo)!;
    const payload: GenerarPdfPayload = {
        tipo,
        nit: clienteSeleccionado.value.nit,
        facturas: [...seleccionadas.value],
        paramsDays: meta.admiteDias ? paramsDays.value : null,
        tasaMensual: store.tasa?.tasaMensual ?? null
    };
    try {
        const archivo = await store.generarPdf(payload);
        revocarPreview();
        previewArchivo.value = archivo;
        previewSrc.value = URL.createObjectURL(archivo.blob);
        previewTitulo.value = `${meta.etiqueta} — ${clienteSeleccionado.value.nombre}`;
        previewPayload.value = payload;
        preview.value = true;
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo generar la carta.'));
    }
}

async function enviar(destinatario: string) {
    if (!previewPayload.value) return;
    try {
        const { message } = await store.enviarCorreo({ ...previewPayload.value, destinatario });
        notificar('success', message);
        preview.value = false;
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo enviar la carta.'));
    }
}
</script>

<template>
    <BaseBreadcrumb :title="page.title" :breadcrumbs="breadcrumbs"></BaseBreadcrumb>

    <v-card elevation="10">
        <v-card-text>
            <div class="d-flex align-center flex-wrap ga-2 mb-4">
                <v-chip color="warning" variant="tonal" size="small">
                    <v-icon start size="16">mdi-percent-outline</v-icon>
                    Tasa de mora:
                    <strong class="ms-1">{{ store.tasa ? `${store.tasa.tasaMensual}% mensual` : '…' }}</strong>
                </v-chip>
                <v-btn
                    v-if="puedeEditarTasa"
                    size="x-small"
                    variant="text"
                    color="primary"
                    :loading="store.cargandoTasa"
                    @click="dialogTasa = true"
                >
                    <v-icon start size="16">mdi-pencil</v-icon>Editar
                </v-btn>
            </div>

            <v-row>
                <v-col cols="12" md="7">
                    <v-autocomplete
                        v-model="clienteSeleccionado"
                        v-model:search="busqueda"
                        :items="store.clientes"
                        :loading="store.buscandoClientes"
                        :item-props="(c: ClienteBusqueda) => ({ title: c.nombre, subtitle: `NIT: ${c.nit}` })"
                        item-title="nombre"
                        return-object
                        label="Buscar cliente por NIT o nombre"
                        placeholder="Escribe al menos 2 caracteres"
                        prepend-inner-icon="mdi-account-search"
                        hide-no-data
                        clearable
                        no-filter
                    />
                </v-col>
            </v-row>

            <v-alert
                v-if="clienteSeleccionado && !store.buscandoFacturas && store.facturas.length === 0"
                type="warning"
                variant="tonal"
                class="mt-2"
            >
                El cliente no tiene facturas con saldo pendiente.
            </v-alert>

            <template v-if="store.facturas.length > 0 || store.buscandoFacturas">
                <div class="d-flex flex-wrap ga-2 my-3">
                    <v-chip color="info" variant="flat">
                        <v-icon start>mdi-account</v-icon> {{ clienteSeleccionado?.nit }} - {{ clienteSeleccionado?.nombre }}
                    </v-chip>
                    <v-chip color="info" variant="tonal">
                        Seleccionadas: {{ seleccionadas.length }} / {{ store.facturas.length }}
                    </v-chip>
                    <v-chip color="success" variant="tonal">Saldo seleccionado: {{ formatCurrency(totalSeleccionado) }}</v-chip>
                </div>

                <v-data-table
                    v-model="seleccionadas"
                    :headers="headers"
                    :items="store.facturas"
                    :loading="store.buscandoFacturas"
                    item-value="comprobante"
                    show-select
                    density="compact"
                    items-per-page="-1"
                >
                    <template #item.fechaFactura="{ item }">{{ formatFecha(item.fechaFactura) }}</template>
                    <template #item.fechaVencimiento="{ item }">
                        <v-chip :color="item.diasMora > 0 ? 'error' : 'info'" size="small" variant="tonal">
                            {{ formatFecha(item.fechaVencimiento) }}
                        </v-chip>
                    </template>
                    <template #item.valorInicial="{ item }">{{ formatCurrency(item.valorInicial) }}</template>
                    <template #item.saldo="{ item }">{{ formatCurrency(item.saldo) }}</template>
                </v-data-table>

                <v-row class="mt-2" align="center">
                    <v-col cols="12" sm="4" md="3">
                        <v-text-field
                            v-model.number="paramsDays"
                            type="number"
                            min="0"
                            label="Días extra de mora (opcional)"
                            hint="Solo aplica a Prejurídico con interés"
                            persistent-hint
                            density="compact"
                            clearable
                        />
                    </v-col>
                </v-row>

                <div class="d-flex flex-wrap ga-3 mt-3">
                    <v-btn
                        v-for="carta in CARTAS"
                        :key="carta.tipo"
                        :color="carta.color"
                        :loading="store.generandoPdf"
                        :disabled="sinSeleccion"
                        @click="generar(carta.tipo)"
                    >
                        <v-icon start>{{ carta.icono }}</v-icon>{{ carta.etiqueta }}
                    </v-btn>
                </div>
            </template>
        </v-card-text>
    </v-card>

    <PdfPreviewDialog
        v-model="preview"
        :src="previewSrc"
        :archivo="previewArchivo"
        :titulo="previewTitulo"
        :correo-sugerido="correoSugerido"
        :enviando="store.enviandoCorreo"
        @enviar="enviar"
    />

    <TasaInteresDialog v-model="dialogTasa" :tasa="store.tasa" @guardar="guardarTasa" />

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top right" timeout="4000">
        {{ snackbar.text }}
    </v-snackbar>
</template>
