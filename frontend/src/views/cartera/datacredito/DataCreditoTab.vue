<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { format as formatDate, startOfMonth, subDays } from 'date-fns';
import PeriodoToolbar from './PeriodoToolbar.vue';
import ClientesTable from './ClientesTable.vue';
import NovedadesResumen from './NovedadesResumen.vue';
import FacturasModal from './FacturasModal.vue';
import { useDataCreditoStore } from '@/stores/cartera/dataCreditoStore';
import type { ClienteReporte, FacturaCliente } from '@/types/dataCredito';

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
const clienteActual = ref<{ tercero: string; nombre: string; saldo: number } | null>(null);

const periodoActual = computed(() => store.periodos.find((p) => p.fechaReporte === fechaSeleccionada.value) ?? null);
const cerrada = computed(() => (periodoActual.value ? !periodoActual.value.abierto : false));

onMounted(async () => {
    try {
        if (store.periodos.length === 0) {
            await store.cargarPeriodos();
        }
        // Preselecciona el cierre del mes anterior si ya existe como periodo — igual criterio que el proyecto de referencia.
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
        await store.buscarClientes(fechaSeleccionada.value);
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudieron cargar los clientes.'));
    }
}

async function abrirFacturas(cliente: ClienteReporte) {
    if (!fechaSeleccionada.value) return;
    clienteActual.value = { tercero: cliente.nit, nombre: cliente.cliente, saldo: cliente.saldo };
    dialogFacturas.value = true;
    try {
        await store.buscarFacturasCliente(cliente.nit, fechaSeleccionada.value);
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudieron cargar las facturas del cliente.'));
    }
}

async function guardarNovedades(facturas: FacturaCliente[]) {
    if (!clienteActual.value || !fechaSeleccionada.value) return;
    try {
        await store.guardarNovedades({ tercero: clienteActual.value.tercero, fechaReporte: fechaSeleccionada.value, facturas });
        notificar('success', 'Se reportaron las facturas correctamente.');
        dialogFacturas.value = false;
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo guardar el reporte.'));
    }
}

async function reclasificarFactura({ factura, motivo }: { factura: FacturaCliente; motivo: string }) {
    if (!clienteActual.value || !fechaSeleccionada.value) return;
    try {
        await store.reclasificarFactura({
            tercero: clienteActual.value.tercero,
            fechaReporte: fechaSeleccionada.value,
            factura,
            motivo: motivo || undefined
        });
        notificar('success', 'Se reclasificó la factura correctamente.');
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo reclasificar la factura.'));
    }
}

async function cerrarMes() {
    if (!periodoActual.value || !fechaSeleccionada.value) return;
    try {
        await store.cerrarPeriodo({ periodoId: periodoActual.value.id, fechaReporte: fechaSeleccionada.value });
        notificar('success', 'Se cerró el período correctamente.');
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo cerrar el período.'));
    }
}

async function generarTxt() {
    if (!fechaSeleccionada.value) return;
    try {
        await store.generarTxt(fechaSeleccionada.value);
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo generar el plano TXT.'));
    }
}

async function generarPlanoExcel() {
    if (!fechaSeleccionada.value) return;
    try {
        await store.generarPlanoExcel(fechaSeleccionada.value);
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo generar el plano Excel.'));
    }
}

async function generarExcel() {
    if (!fechaSeleccionada.value) return;
    try {
        await store.generarExcel(fechaSeleccionada.value);
    } catch (e) {
        notificar('error', mensajeError(e, 'No se pudo generar el Excel.'));
    }
}
</script>

<template>
    <div>
        <PeriodoToolbar
            v-model:periodo="fechaSeleccionada"
            v-model:filtro="filtro"
            :periodos="store.periodos"
            :buscando="store.buscandoClientes"
            :disabled="store.loadingPeriodos"
            @buscar="buscar"
        >
            <template #acciones>
                <v-btn
                    v-if="store.clientes.length > 0 && !cerrada"
                    color="warning"
                    :loading="store.cerrandoPeriodo"
                    @click="cerrarMes"
                >
                    <v-icon start>mdi-lock</v-icon>Cerrar mes
                </v-btn>
                <v-btn
                    v-if="store.clientes.length > 0 && cerrada"
                    color="primary"
                    :loading="store.generandoTxt"
                    @click="generarTxt"
                >
                    <v-icon start>mdi-file-document-outline</v-icon>Plano TXT
                </v-btn>
                <v-btn
                    v-if="store.clientes.length > 0 && cerrada"
                    color="orange"
                    :loading="store.generandoPlanoExcel"
                    @click="generarPlanoExcel"
                >
                    <v-icon start>mdi-file-excel</v-icon>Plano Excel
                </v-btn>
                <v-btn v-if="store.clientes.length > 0" color="success" :loading="store.generandoExcel" @click="generarExcel">
                    <v-icon start>mdi-file-excel</v-icon>Excel
                </v-btn>
            </template>
        </PeriodoToolbar>

        <NovedadesResumen :resumen="store.resumenNovedades" />

        <ClientesTable
            class="mt-5"
            titulo="Clientes"
            :clientes="store.clientes"
            :loading="store.buscandoClientes"
            :filtro="filtro"
            accion-label="Facturas"
            accion-icono="mdi mdi-cash-multiple"
            @accion="abrirFacturas"
        />

        <FacturasModal
            v-model="dialogFacturas"
            variant="normal"
            :cliente-documento="clienteActual?.tercero ?? ''"
            :cliente-nombre="clienteActual?.nombre ?? ''"
            :saldo-total="clienteActual?.saldo ?? 0"
            :facturas="store.facturasCliente"
            :loading="store.buscandoFacturas"
            :guardando="store.guardandoNovedades"
            :cerrada="cerrada"
            @guardar="guardarNovedades"
            @reclasificar="reclasificarFactura"
        />

        <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top right" timeout="4000">
            {{ snackbar.text }}
        </v-snackbar>
    </div>
</template>
