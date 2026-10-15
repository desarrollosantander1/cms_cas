<script setup lang="ts">
withDefaults(
    defineProps<{
        modelValue: boolean;
        title: string;
        text: string;
        loading?: boolean;
        confirmText?: string;
        confirmColor?: string;
    }>(),
    { loading: false, confirmText: 'Eliminar', confirmColor: 'error' },
);

const emit = defineEmits<{ 'update:modelValue': [boolean]; confirm: []; cancel: [] }>();

function cancelar() {
    emit('cancel');
    emit('update:modelValue', false);
}
</script>

<template>
    <v-dialog :model-value="modelValue" max-width="480px" persistent @update:model-value="emit('update:modelValue', $event)">
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h5">{{ title }}</span>
            </v-card-title>
            <v-card-text>
                <v-alert type="warning" variant="tonal" density="compact">{{ text }}</v-alert>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="secondary" variant="flat" :disabled="loading" @click="cancelar">Cancelar</v-btn>
                <v-btn :color="confirmColor" variant="flat" :loading="loading" :disabled="loading" @click="emit('confirm')">{{ confirmText }}</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>
