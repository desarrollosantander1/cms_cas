<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { formatCurrency, formatFecha } from '@/utils/helpers/format';
import { NOVEDADES_FILTRO, NOVEDAD_RECLASIFICADO_DEFECTO, type FacturaCliente } from '@/types/dataCredito';

const dialog = defineModel<boolean>({ default: false });

const props = withDefaults(
    defineProps<{
        variant: 'normal' | 'reclasificado';
        clienteDocumento: string;
        clienteNombre: string;
        saldoTotal: number;
        facturas: FacturaCliente[];
        loading?: boolean;
        guardando?: boolean;
        cerrada?: boolean;
    }>(),
    { loading: false, guardando: false, cerrada: false }
);

const emit = defineEmits<{
    guardar: [facturas: FacturaCliente[]];
    reclasificar: [payload: { factura: FacturaCliente; motivo: string }];
}>();

/** Punto de entrada para crear la PRIMERA reclasificación de una factura —
 * distinto del flujo de edición de reclasificados ya existentes (variant
 * 'reclasificado'). Depende únicamente de estar en la pestaña Datacrédito
 * con el período abierto — sin chequeo de rol, cualquiera que vea la
 * pestaña normal puede reclasificar. */
const puedeReclasificar = computed(() => props.variant === 'normal' && !props.cerrada);

const dialogMotivo = ref(false);
const facturaAReclasificar = ref<FacturaCliente | null>(null);
const motivo = ref('');

/** Copia editable local — el modal nunca muta el prop directamente. */
const filas = ref<FacturaCliente[]>([]);
watch(
    () => props.facturas,
    (nuevas) => {
        filas.value = nuevas.map((f) => ({ ...f }));
    },
    { immediate: true }
);

const novedadesFiltradas = ref<number[]>([]);
watch(dialog, (abierto) => {
    if (abierto) novedadesFiltradas.value = [];
});

const filasVisibles = computed(() => {
    if (props.variant !== 'normal' || novedadesFiltradas.value.length === 0) {
        return filas.value;
    }
    return filas.value.filter((f) => novedadesFiltradas.value.includes(f.novedad));
});

/**
 * Anchos fijos (px) — junto con table-layout:fixed en .tabla-compacta, hacen
 * que las columnas quepan sin scroll horizontal en el modal fullscreen.
 */
const headers = computed(() => {
    const col = (title: string, key: string, width: number, opts: { sortable?: boolean } = {}) => ({
        title,
        key,
        width,
        ...opts
    });

    const base = [
        col('Documento', 'factura', 85),
        col('F. Emisión', 'fechaEmision', 95),
        col('F. Vencimiento', 'fechaVencimiento', 95),
        col('Valor', 'valor', 90),
        col('Saldo anterior', 'saldoAnterior', 95),
        col('Saldo', 'saldo', 125),
        col('Diferencia', 'diferencia', 85)
    ];
    if (props.variant === 'normal') {
        base.push(col('Dias', 'dias', 55), col('Dias vencidos', 'diasVencidos', 75));
    }
    if (props.variant === 'reclasificado' || puedeReclasificar.value) {
        base.push(col('Novedad', 'novedad', 80));
    }
    base.push(col('Omitir', 'omitir', 55));
    if (puedeReclasificar.value) {
        base.push(col('Reclasificar', 'reclasificarAccion', 110, { sortable: false }));
    }
    return base;
});

function soloDigitos(event: KeyboardEvent) {
    if (!/^[0-9]*$/.test(event.key)) event.preventDefault();
}

function actualizarSaldo(fila: FacturaCliente, valor: string) {
    const numero = parseInt(valor.replace(/[^\d]/g, ''), 10);
    fila.saldo = isNaN(numero) ? 0 : numero;
}

function actualizarNovedad(fila: FacturaCliente, valor: string) {
    const numero = parseInt(valor, 10);
    fila.novedad = isNaN(numero) || numero < 1 || numero > 13 ? NOVEDAD_RECLASIFICADO_DEFECTO : numero;
}

function toggleOmitir(fila: FacturaCliente, omitir: boolean | null) {
    fila.reportar = omitir ? 0 : 1;
}

function abrirConfirmacionReclasificar(fila: FacturaCliente) {
    facturaAReclasificar.value = fila;
    motivo.value = '';
    dialogMotivo.value = true;
}

function confirmarReclasificar() {
    if (!facturaAReclasificar.value) return;
    const fila = facturaAReclasificar.value;
    emit('reclasificar', {
        factura: { ...fila, novedad: fila.novedad || NOVEDAD_RECLASIFICADO_DEFECTO },
        motivo: motivo.value.trim()
    });
    dialogMotivo.value = false;
    facturaAReclasificar.value = null;
}

const excedeSaldoAnterior = computed(() => filas.value.some((f) => f.saldo > f.saldoAnterior));

function guardar() {
    emit('guardar', filas.value);
}
</script>

<template>
    <v-dialog v-model="dialog" fullscreen :scrim="false" transition="dialog-bottom-transition" persistent>
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h5">{{ variant === 'reclasificado' ? 'Reclasificar facturas' : 'Marcar novedades' }}</span>
            </v-card-title>
            <v-card-text class="pt-4">
                <v-alert v-if="cerrada" type="warning" variant="tonal" class="mb-3">La fecha del periodo se encuentra cerrada.</v-alert>
                <v-alert v-else-if="!loading && facturas.length === 0" type="warning" variant="tonal" class="mb-3">Sin facturas disponibles.</v-alert>

                <v-form v-if="variant === 'normal' && facturas.length > 0" class="mb-4">
                    <div class="text-caption mb-1">Filtrar por novedad</div>
                    <v-chip-group v-model="novedadesFiltradas" multiple filter selected-class="text-primary">
                        <v-chip v-for="opcion in NOVEDADES_FILTRO" :key="opcion.valor" :value="opcion.valor" variant="outlined">
                            {{ opcion.texto }}
                        </v-chip>
                    </v-chip-group>
                </v-form>

                <div v-if="filasVisibles.length > 0" class="d-flex flex-wrap ga-2 mb-3">
                    <v-chip color="info" variant="flat"><v-icon start>mdi-account</v-icon> {{ clienteDocumento }} - {{ clienteNombre }}</v-chip>
                    <v-chip color="info" variant="tonal">Total saldo: {{ formatCurrency(saldoTotal) }}</v-chip>
                </div>

                <v-data-table
                    :headers="headers"
                    :items="filasVisibles"
                    :loading="loading"
                    density="compact"
                    items-per-page="-1"
                    class="tabla-compacta"
                >
                    <template #item.fechaEmision="{ item }">
                        <v-chip color="info" :size="'small'" variant="tonal">{{ formatFecha(item.fechaEmision) }}</v-chip>
                    </template>
                    <template #item.fechaVencimiento="{ item }">
                        <v-chip
                            :color="item.novedad !== 1 && item.novedad !== 5 ? 'error' : 'info'"
                            :size="'small'"
                            variant="tonal"
                        >
                            {{ formatFecha(item.fechaVencimiento) }}
                        </v-chip>
                    </template>
                    <template #item.valor="{ item }">{{ formatCurrency(item.valor) }}</template>
                    <template #item.saldoAnterior="{ item }">{{ formatCurrency(item.saldoAnterior) }}</template>
                    <template #item.saldo="{ item }">
                        <v-text-field
                            v-if="item.saldoAnterior > 0 && !cerrada"
                            :model-value="formatCurrency(item.saldo)"
                            density="compact"
                            hide-details
                            style="min-width: 100px; max-width: 120px"
                            @keypress="soloDigitos"
                            @paste.prevent
                            @update:model-value="(v: string) => actualizarSaldo(item, v)"
                        />
                        <span v-else>{{ formatCurrency(item.saldo) }}</span>
                    </template>
                    <template #item.diferencia="{ item }">{{ formatCurrency(item.saldoAnterior - item.saldo) }}</template>
                    <template #item.diasVencidos="{ item }">
                        <v-chip
                            :color="'error'"
                            :size="'small'"
                            variant="tonal"
                        >
                            {{ item.diasVencidos }}
                        </v-chip>
                    </template>
                    <template v-if="variant === 'reclasificado' || puedeReclasificar" #item.novedad="{ item }">
                        <v-text-field
                            v-if="variant === 'reclasificado' && !cerrada"
                            :model-value="item.novedad"
                            type="number"
                            min="1"
                            max="13"
                            density="compact"
                            hide-details
                            style="max-width: 90px"
                            @keypress="soloDigitos"
                            @update:model-value="(v: string) => actualizarNovedad(item, v)"
                        />
                        <span v-else>{{ item.novedad || NOVEDAD_RECLASIFICADO_DEFECTO }}</span>
                    </template>
                    <template #item.omitir="{ item }">
                        <v-checkbox
                            :model-value="item.reportar === 0"
                            :disabled="cerrada"
                            hide-details
                            @update:model-value="(v: boolean | null) => toggleOmitir(item, v)"
                        />
                    </template>
                    <template v-if="puedeReclasificar" #item.reclasificarAccion="{ item }">
                        <v-btn
                            size="x-small"
                            color="warning"
                            variant="tonal"
                            :disabled="item.saldo > item.saldoAnterior"
                            @click="abrirConfirmacionReclasificar(item)"
                        >
                            <v-icon start size="14">mdi-swap-horizontal</v-icon>Reclasificar
                        </v-btn>
                    </template>
                </v-data-table>

                <v-alert v-if="excedeSaldoAnterior" type="error" variant="tonal" class="mt-3">
                    El valor a reportar no puede ser mayor al saldo anterior.
                </v-alert>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="error" variant="flat" :disabled="guardando" @click="dialog = false">Cerrar</v-btn>
                <v-btn
                    v-if="!cerrada && facturas.length > 0"
                    color="success"
                    variant="flat"
                    :loading="guardando"
                    :disabled="excedeSaldoAnterior"
                    @click="guardar"
                >
                    <v-icon start>mdi-content-save</v-icon>Guardar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-dialog v-model="dialogMotivo" max-width="500px" persistent>
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h6">Confirmar reclasificación</span>
            </v-card-title>
            <v-card-text class="pt-4">
                <p class="mb-3">
                    ¿Reclasificar la factura <strong>{{ facturaAReclasificar?.factura }}</strong>? Pasará a la pestaña
                    Reclasificados, donde podrás asignarle la novedad manual.
                </p>
                <v-textarea v-model="motivo" label="Motivo (opcional)" rows="3" auto-grow hide-details />
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="error" variant="flat" @click="dialogMotivo = false">Cancelar</v-btn>
                <v-btn color="warning" variant="flat" @click="confirmarReclasificar">
                    <v-icon start>mdi-swap-horizontal</v-icon>Reclasificar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<style scoped>
/* Solo en fullscreen (variant "normal", período abierto): reduce fuente y
   padding de la tabla para que las 12 columnas quepan sin scroll horizontal,
   como pidió el usuario ("no importa que se rebaje el tamaño de la fuente"). */
.tabla-compacta {
    font-size: 12px;
}

.tabla-compacta :deep(table) {
    table-layout: fixed;
    width: 100%;
}

.tabla-compacta :deep(.v-data-table__th),
.tabla-compacta :deep(.v-data-table__td) {
    padding: 0 6px;
    white-space: normal;
    word-break: break-word;
}

.tabla-compacta :deep(.v-data-table-header__content) {
    font-size: 12px;
    white-space: normal;
}

.tabla-compacta :deep(.v-field__input) {
    font-size: 12px;
    padding-top: 2px;
    padding-bottom: 2px;
    min-height: 32px;
}
</style>
