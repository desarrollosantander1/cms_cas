<script setup lang="ts">
import { computed } from 'vue';
import { formatFecha } from '@/utils/helpers/format';
import type { PeriodoCierre } from '@/types/dataCredito';

const periodo = defineModel<string | null>('periodo', { default: null });
const filtro = defineModel<string>('filtro', { default: '' });

const props = withDefaults(
    defineProps<{
        periodos: PeriodoCierre[];
        buscando?: boolean;
        disabled?: boolean;
    }>(),
    { buscando: false, disabled: false }
);

const emit = defineEmits<{ buscar: [] }>();

/** Opciones del select con la etiqueta visual armada (DD/MM/YYYY, + "- CERRADA" si el periodo ya no admite cambios). */
const opciones = computed(() =>
    props.periodos.map((item) => ({
        value: item.fechaReporte,
        label: formatFecha(item.fechaReporte) + (item.abierto ? '' : ' - CERRADA')
    }))
);
</script>

<template>
    <!--
        pt-3: este toolbar es lo primero que renderiza cada v-window-item de
        DataCreditoAdmin.vue (tabs Datacrédito/Reclasificados). v-window usa
        overflow:hidden para animar el cambio de pestaña, y su borde superior
        queda pegado al top del primer hijo — el label flotante de "Periodo"/
        "Filtrar por..." sobresale ~10px por encima del borde del campo (así
        se dibuja el notch en un v-field outlined), así que sin este espacio
        ese label queda recortado por el overflow:hidden del v-window, no por
        line-height ni por ningún otro estilo del campo en sí.
    -->
    <div class="d-flex flex-wrap align-center ga-3 pt-3">
        <v-select
            v-model="periodo"
            :items="opciones"
            item-title="label"
            item-value="value"
            label="Periodo"
            placeholder="-- Ninguno --"
            :disabled="disabled"
            density="compact"
            hide-details
            style="max-width: 240px"
            @update:model-value="emit('buscar')"
        />
        <v-text-field
            v-model="filtro"
            label="Filtrar por..."
            :disabled="disabled"
            density="compact"
            hide-details
            style="max-width: 220px"
        />
        <v-btn color="primary" :disabled="disabled" :loading="buscando" @click="emit('buscar')">
            <v-icon>mdi-magnify</v-icon>
        </v-btn>
        <slot name="acciones" />
    </div>
</template>
