<script setup lang="ts">
import { ref, watch } from 'vue';
import { descargarArchivo } from '@/utils/helpers/descargarArchivo';
import type { ArchivoDescargado } from '@/utils/helpers/fetch-wrapper';

const dialog = defineModel<boolean>({ default: false });

const props = defineProps<{
    /** Object URL del blob, para el <iframe>. */
    src: string | null;
    archivo: ArchivoDescargado | null;
    titulo: string;
    correoSugerido?: string | null;
    enviando?: boolean;
}>();

const emit = defineEmits<{ enviar: [destinatario: string] }>();

const destinatario = ref('');
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

watch(
    () => [dialog.value, props.correoSugerido] as const,
    ([abierto]) => {
        if (abierto) destinatario.value = props.correoSugerido ?? '';
    },
    { immediate: true }
);

function descargar() {
    if (props.archivo) descargarArchivo(props.archivo);
}
</script>

<template>
    <v-dialog v-model="dialog" fullscreen :scrim="false" transition="dialog-bottom-transition">
        <v-card class="d-flex flex-column">
            <v-card-title class="pa-4 bg-secondary d-flex align-center flex-wrap ga-2">
                <span class="text-h6">{{ titulo }}</span>
                <v-spacer />
                <v-text-field
                    v-model="destinatario"
                    label="Enviar a"
                    density="compact"
                    hide-details
                    variant="solo"
                    prepend-inner-icon="mdi-email-outline"
                    style="max-width: 320px"
                    :disabled="enviando"
                />
                <v-btn
                    color="primary"
                    variant="flat"
                    :loading="enviando"
                    :disabled="!EMAIL_RE.test(destinatario)"
                    @click="emit('enviar', destinatario)"
                >
                    <v-icon start>mdi-send</v-icon>Enviar
                </v-btn>
                <v-btn color="success" variant="flat" :disabled="!archivo" @click="descargar">
                    <v-icon start>mdi-download</v-icon>Descargar
                </v-btn>
                <v-btn icon variant="text" @click="dialog = false"><v-icon>mdi-close</v-icon></v-btn>
            </v-card-title>
            <v-card-text class="pa-0 flex-grow-1">
                <iframe v-if="src" :src="src" title="Vista previa de la carta" style="width: 100%; height: 100%; border: 0" />
            </v-card-text>
        </v-card>
    </v-dialog>
</template>
