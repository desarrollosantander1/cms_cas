<script setup lang="ts">
import { ref, watch } from 'vue';
import type { TasaInteres } from '@/types/generadorCartas';

const dialog = defineModel<boolean>({ default: false });

const props = defineProps<{
    tasa: TasaInteres | null;
    guardando?: boolean;
}>();

const emit = defineEmits<{ guardar: [tasaMensual: number] }>();

const valor = ref<number | null>(null);

watch(dialog, (abierto) => {
    if (abierto) valor.value = props.tasa?.tasaMensual ?? 2;
});

const valido = () => valor.value !== null && Number.isFinite(valor.value) && valor.value > 0;

function guardar() {
    if (valido()) emit('guardar', valor.value as number);
}
</script>

<template>
    <v-dialog v-model="dialog" max-width="460" persistent>
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h6">Tasa de interés de mora</span>
            </v-card-title>
            <v-card-text class="pt-4">
                <p class="text-body-2 mb-4">
                    Se aplica a todas las cartas jurídicas:
                    <code>interés = saldo × (tasa / 100) × días de mora / 30</code>.
                </p>
                <v-text-field
                    v-model.number="valor"
                    type="number"
                    min="0"
                    step="0.1"
                    label="Tasa mensual (%)"
                    suffix="% mensual"
                    :disabled="guardando"
                    autofocus
                />
                <p v-if="tasa && !tasa.porDefecto" class="text-caption text-medium-emphasis">
                    Actual: {{ tasa.tasaMensual }}% — actualizada por {{ tasa.actualizadoPor }}
                </p>
                <p v-else class="text-caption text-medium-emphasis">Actualmente se usa el 2% por defecto (sin registro).</p>
                <v-alert type="info" variant="tonal" density="compact" class="mt-3">
                    Este cambio aplica solo a tu sesión actual y no se guarda en base de datos; al recargar la página se vuelve a
                    cargar el valor configurado.
                </v-alert>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="error" variant="flat" :disabled="guardando" @click="dialog = false">Cancelar</v-btn>
                <v-btn color="success" variant="flat" :loading="guardando" :disabled="!valido()" @click="guardar">
                    <v-icon start>mdi-content-save</v-icon>Guardar
                </v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
