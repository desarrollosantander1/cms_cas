<script setup lang="ts">
import { ref } from 'vue';
import { authService } from '@/services/authService';

const valid = ref(true);
const usuario = ref('');
const usuarioRules = ref([(v: string) => !!v || 'El usuario es requerido']);

const enviando = ref(false);
const enviado = ref(false);
const error = ref('');

async function enviar() {
    if (!usuario.value.trim()) {
        return;
    }
    enviando.value = true;
    error.value = '';
    try {
        await authService.forgotPassword(usuario.value);
        // Respuesta genérica siempre, exista o no el usuario — así responde el backend.
        enviado.value = true;
    } catch {
        error.value = 'No se pudo procesar la solicitud. Intenta de nuevo.';
    } finally {
        enviando.value = false;
    }
}
</script>
<template>
    <v-form v-if="!enviado" ref="form" v-model="valid" lazy-validation class="mt-sm-13 mt-8" @submit.prevent="enviar">
        <v-label class="text-subtitle-1 font-weight-semibold pb-2 text-lightText">Usuario</v-label>
        <VTextField v-model="usuario" :rules="usuarioRules" required></VTextField>
        <v-alert v-if="error" type="error" class="mt-3 mb-3">{{ error }}</v-alert>
        <v-btn size="large" color="primary" block :loading="enviando" type="submit" flat>Recuperar contraseña</v-btn>
    </v-form>
    <v-alert v-else type="success" class="mt-sm-13 mt-8">
        Si el usuario existe, recibirás un enlace de recuperación en el correo configurado.
    </v-alert>
</template>
