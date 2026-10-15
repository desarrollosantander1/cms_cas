<script setup lang="ts">
import { computed } from 'vue';
import { formatCurrency } from '@/utils/helpers/format';
import type { ClienteReporte } from '@/types/dataCredito';

const props = withDefaults(
    defineProps<{
        titulo: string;
        clientes: ClienteReporte[];
        loading?: boolean;
        filtro?: string;
        accionLabel: string;
        accionIcono: string;
        accionColor?: string;
        accionDisabled?: boolean;
    }>(),
    { loading: false, filtro: '', accionColor: 'success', accionDisabled: false }
);

const emit = defineEmits<{ accion: [cliente: ClienteReporte] }>();

const headers = [
    { title: '#', key: 'numeracion', width: 60 },
    { title: 'Tipo', key: 'tipo' },
    { title: 'NIT', key: 'nit' },
    { title: 'Cliente', key: 'cliente' },
    { title: 'Valor inicial', key: 'saldoInicialLabel' },
    { title: 'Saldo', key: 'saldoLabel' },
    { title: 'Obligaciones', key: 'acciones', sortable: false}
];

const filas = computed(() =>
    props.clientes.map((cliente, index) => ({
        ...cliente,
        numeracion: index + 1,
        saldoInicialLabel: formatCurrency(cliente.saldoInicial),
        saldoLabel: formatCurrency(cliente.saldo)
    }))
);
</script>

<template>
    <div>
        <div class="d-flex align-center justify-space-between mb-2">
            <span class="text-subtitle-1 font-weight-medium">{{ titulo }}</span>
            <span class="text-caption text-medium-emphasis">{{ filas.length }} registro{{ filas.length === 1 ? '' : 's' }}</span>
        </div>

        <v-data-table
            class="border rounded-md"
            density="compact"
            :headers="headers"
            :items="filas"
            :loading="loading"
            :search="filtro"
            :items-per-page="15"
            :items-per-page-options="[15, 30, 50, 100]"
        >
            <template #item.acciones="{ item }">
                <v-btn size="small" :color="accionColor" :disabled="accionDisabled" @click="emit('accion', item)">
                    <v-icon start>{{ accionIcono }}</v-icon>
                    {{ accionLabel }}
                </v-btn>
            </template>
        </v-data-table>
    </div>
</template>
