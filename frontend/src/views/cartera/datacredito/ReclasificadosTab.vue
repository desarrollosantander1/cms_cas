<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { format as formatDate, startOfMonth, subDays } from 'date-fns';
import { formatFecha } from '@/utils/helpers/format';
import PeriodoToolbar from './PeriodoToolbar.vue';
import ClientesTable from './ClientesTable.vue';
import FacturasModal from './FacturasModal.vue';
import TodasFacturasModal from './TodasFacturasModal.vue';
import { useDataCreditoStore } from '@/stores/cartera/dataCreditoStore';
import type { ClienteReporte, FacturaCliente, FacturaReclasificadaResumen } from '@/types/dataCredito';

const store = useDataCreditoStore();
const snackbar = reactive({ show: false, color: 'success', text: '' });

function notificar(color: 'success' | 'error', text: string) {
    snackbar.color = color;
    snackbar.text = text;
    snackbar.show = true;
}

function mensajeError(e: unknown, fallback: string): string {
    return typeof e === 'string' ? e : fallback;
}

const fechaSeleccionada = ref<string | null>(null);
const filtro = ref('');
const dialogFacturas = ref(false);
const dialogTodasFacturas = ref(false);
const clienteActual = ref<{ tercero: string; nombre: string; saldo: number } | null>(null);

const cerrada = computed(() => {
    const periodo = store.periodos.find((p) => p.fechaReporte === fechaSeleccionada.value);
    return periodo ? !periodo.abierto : false;
});

const periodoLabel = computed(() => (fechaSeleccionada.value ? formatFecha(fechaSeleccionada.value) : ''));

onMounted(async () => {
    try {
        if (store.periodos.length === 0) {
            await store.cargarPeriodos();
        }
        const sugerida = formatDate(subDays(startOfMonth(new Date()), 1), 'yyyy-MM-dd');
        const existente = store.periodos.find((p) => p.fechaReporte === sugerida);
        if (existente) {
            fechaSeleccionada.value = existente.fechaReporte;
            await buscar();
        }
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudieron cargar los periodos disponibles.'));
    }
});

async function buscar() {
    if (!fechaSeleccionada.value) return;
    filtro.value = '';
    try {
        await store.buscarClientesReclasificados(fechaSeleccionada.value);
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudieron cargar los clientes reclasificados.'));
    }
}

async function abrirFacturas(cliente: ClienteReporte) {
    if (!fechaSeleccionada.value) return;
    clienteActual.value = { tercero: cliente.nit, nombre: cliente.cliente, saldo: cliente.saldo };
    dialogFacturas.value = true;
    try {
        await store.buscarFacturasReclasificadas(cliente.nit, fechaSeleccionada.value);
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudieron cargar las facturas del cliente.'));
    }
}

async function guardarReclasificaciones(facturas: FacturaCliente[]) {
    if (!clienteActual.value || !fechaSeleccionada.value) return;
    try {
        await store.guardarReclasificaciones({ tercero: clienteActual.value.tercero, fechaReporte: fechaSeleccionada.value, facturas });
        notificar('success', 'Se guardaron las reclasificaciones correctamente.');
        dialogFacturas.value = false;
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo guardar la reclasificación.'));
    }
}

async function abrirTodasFacturas() {
    if (!fechaSeleccionada.value) return;
    dialogTodasFacturas.value = true;
    try {
        await store.cargarTodasFacturasReclasificadas(fechaSeleccionada.value);
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudieron cargar todas las facturas.'));
    }
}

async function guardarTodasFacturas(facturas: FacturaReclasificadaResumen[]) {
    if (!fechaSeleccionada.value) return;
    try {
        await store.guardarTodasFacturasReclasificadas(fechaSeleccionada.value, facturas);
        notificar('success', `Se guardaron ${facturas.length} facturas correctamente.`);
        dialogTodasFacturas.value = false;
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudieron guardar las facturas seleccionadas.'));
    }
}
</script>

<template>
    <div>
        <PeriodoToolbar
            v-model:periodo="fechaSeleccionada"
            v-model:filtro="filtro"
            :periodos="store.periodos"
            :buscando="store.buscandoClientesReclasificados"
            :disabled="store.loadingPeriodos"
            @buscar="buscar"
        >
            <template #acciones>
                <v-btn
                    color="warning"
                    :disabled="cerrada || store.clientesReclasificados.length === 0"
                    @click="abrirTodasFacturas"
                >
                    <v-icon start>mdi-format-list-checks</v-icon>Ver todas y guardar
                </v-btn>
            </template>
        </PeriodoToolbar>

        <ClientesTable
            class="mt-5"
            titulo="Clientes reclasificados"
            :clientes="store.clientesReclasificados"
            :loading="store.buscandoClientesReclasificados"
            :filtro="filtro"
            accion-label="Reclasificar"
            accion-icono="mdi-swap-horizontal"
            accion-color="warning"
            :accion-disabled="cerrada"
            @accion="abrirFacturas"
        />

        <FacturasModal
            v-model="dialogFacturas"
            variant="reclasificado"
            :cliente-documento="clienteActual?.tercero ?? ''"
            :cliente-nombre="clienteActual?.nombre ?? ''"
            :saldo-total="clienteActual?.saldo ?? 0"
            :facturas="store.facturasReclasificadas"
            :loading="store.buscandoFacturasReclasificadas"
            :guardando="store.guardandoReclasificaciones"
            :cerrada="cerrada"
            @guardar="guardarReclasificaciones"
        />

        <TodasFacturasModal
            v-model="dialogTodasFacturas"
            :periodo-label="periodoLabel"
            :facturas="store.todasFacturasReclasificadas"
            :loading="store.cargandoTodasFacturas"
            :guardando="store.guardandoTodasFacturas"
            @guardar="guardarTodasFacturas"
        />

        <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top right" timeout="4000">
            {{ snackbar.text }}
        </v-snackbar>
    </div>
</template>
