<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { formatCurrency, formatFecha } from '@/utils/helpers/format';
import type { FacturaReclasificadaResumen } from '@/types/dataCredito';

const dialog = defineModel<boolean>({ default: false });

const props = withDefaults(
    defineProps<{
        periodoLabel: string;
        facturas: FacturaReclasificadaResumen[];
        loading?: boolean;
        guardando?: boolean;
    }>(),
    { loading: false, guardando: false }
);

const emit = defineEmits<{ guardar: [facturas: FacturaReclasificadaResumen[]] }>();

/** Copia local con la marca de selección — arranca con todo tildado, igual que la referencia. */
const filas = ref<(FacturaReclasificadaResumen & { seleccionada: boolean })[]>([]);
watch(
    () => props.facturas,
    (nuevas) => {
        filas.value = nuevas.map((f) => ({ ...f, seleccionada: true }));
    },
    { immediate: true }
);

const seleccionadas = computed(() => filas.value.filter((f) => f.seleccionada));

const headers = [
    { title: 'Guardar', key: 'seleccionada', width: 90 },
    { title: 'Cliente', key: 'cliente' },
    { title: 'Factura', key: 'factura' },
    { title: 'F. Emisión', key: 'fechaEmision' },
    { title: 'F. Vencimiento', key: 'fechaVencimiento' },
    { title: 'Saldo anterior', key: 'saldoAnterior' },
    { title: 'Saldo actual', key: 'saldo' },
    { title: 'Novedad', key: 'novedad' }
];

function seleccionarTodas(valor: boolean) {
    filas.value.forEach((f) => (f.seleccionada = valor));
}

function guardar() {
    emit('guardar', seleccionadas.value);
}
</script>

<template>
    <v-dialog v-model="dialog" max-width="1300px" persistent no-click-animation>
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h5">Vista previa: todas las facturas reclasificadas</span>
            </v-card-title>
            <v-card-text class="pt-4">
                <div v-if="loading" class="text-center py-10">
                    <v-progress-circular indeterminate color="primary" size="48" />
                    <p class="mt-3 text-h6">Cargando todas las facturas...</p>
                </div>

                <template v-else>
                    <v-alert type="info" variant="tonal" class="mb-3">
                        <div class="d-flex justify-space-between align-center flex-wrap ga-2">
                            <span><strong>{{ facturas.length }}</strong> facturas encontradas para el periodo <strong>{{ periodoLabel }}</strong></span>
                            <v-chip color="success">{{ seleccionadas.length }} seleccionadas</v-chip>
                        </div>
                    </v-alert>

                    <div class="d-flex ga-2 mb-3">
                        <v-btn size="small" variant="outlined" color="success" :disabled="filas.length === 0" @click="seleccionarTodas(true)">
                            <v-icon start>mdi-check-all</v-icon>Seleccionar todas
                        </v-btn>
                        <v-btn size="small" variant="outlined" :disabled="filas.length === 0" @click="seleccionarTodas(false)">
                            <v-icon start>mdi-close</v-icon>Deseleccionar todas
                        </v-btn>
                    </div>

                    <v-data-table :headers="headers" :items="filas" density="compact" items-per-page="-1" height="450" fixed-header>
                        <template #item.seleccionada="{ item }">
                            <v-checkbox v-model="item.seleccionada" hide-details />
                        </template>
                        <template #item.cliente="{ item }">
                            <div>{{ item.cliente }}</div>
                            <div class="text-caption text-medium-emphasis">{{ item.nit }}</div>
                        </template>
                        <template #item.factura="{ item }">
                            <v-chip color="primary" size="small">{{ item.factura }}</v-chip>
                        </template>
                        <template #item.fechaEmision="{ item }">{{ formatFecha(item.fechaEmision) }}</template>
                        <template #item.fechaVencimiento="{ item }">{{ formatFecha(item.fechaVencimiento) }}</template>
                        <template #item.saldoAnterior="{ item }">{{ formatCurrency(item.saldoAnterior) }}</template>
                        <template #item.saldo="{ item }">{{ formatCurrency(item.saldo) }}</template>
                        <template #item.novedad="{ item }">
                            <v-chip :color="item.novedad === 9 ? 'secondary' : 'warning'" size="small">{{ item.novedad }}</v-chip>
                        </template>
                    </v-data-table>

                    <div v-if="filas.length === 0" class="text-center text-medium-emphasis py-10">No se encontraron facturas para mostrar.</div>
                </template>
            </v-card-text>
            <v-card-actions>
                <span class="text-caption text-medium-emphasis ml-2">Periodo: {{ periodoLabel || 'No seleccionado' }}</span>
                <v-spacer />
                <v-btn color="error" variant="flat" :disabled="guardando" @click="dialog = false">Cerrar</v-btn>
                <v-btn color="success" variant="flat" :loading="guardando" :disabled="seleccionadas.length === 0" @click="guardar">
                    <v-icon start>mdi-content-save</v-icon>Guardar {{ seleccionadas.length }} facturas
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
