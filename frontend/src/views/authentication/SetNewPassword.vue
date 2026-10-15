<script setup lang="ts">
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import Logo from '@/layouts/full/logo/Logo.vue';
import { authService } from '@/services/authService';

const route = useRoute();
const token = route.params.token as string;

const password = ref('');
const confirmar = ref('');
const enviando = ref(false);
const listo = ref(false);
const error = ref('');
const mostrarPassword = ref(false);
const mostrarConfirmar = ref(false);

const passwordRules = [
    (v: string) => !!v || 'La contraseña es requerida',
    (v: string) => v.length >= 8 || 'Debe tener al menos 8 caracteres',
];

async function enviar() {
    error.value = '';
    if (password.value.length < 8) {
        error.value = 'La contraseña debe tener al menos 8 caracteres.';
        return;
    }
    if (password.value !== confirmar.value) {
        error.value = 'Las contraseñas no coinciden.';
        return;
    }

    enviando.value = true;
    try {
        await authService.resetPassword(token, password.value);
        listo.value = true;
    } catch (e: any) {
        error.value = typeof e === 'string' ? e : 'El enlace de recuperación es inválido o ya expiró.';
    } finally {
        enviando.value = false;
    }
}
</script>

<template>
    <div class="authentication">
        <v-container fluid class="pa-3">
            <v-row class="h-100vh d-flex justify-center align-center">
                <v-col cols="12" lg="4" xl="5" class="d-flex align-center">
                    <v-card rounded="md" elevation="10" class="px-sm-1 px-0 mx-auto">
                        <v-card-item class="pa-sm-8">
                            <div class="d-flex justify-center my-5"><Logo /></div>

                            <template v-if="!listo">
                                <p class="text-subtitle-1 text-center text-10">Ingresa tu nueva contraseña.</p>
                                <v-form class="mt-sm-8 mt-6" @submit.prevent="enviar">
                                    <v-label class="text-subtitle-1 font-weight-semibold pb-2 text-lightText">Nueva contraseña</v-label>
                                    <VTextField
                                        v-model="password"
                                        :rules="passwordRules"
                                        :type="mostrarPassword ? 'text' : 'password'"
                                        :append-inner-icon="mostrarPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                        required
                                        @click:append-inner="mostrarPassword = !mostrarPassword"
                                    ></VTextField>
                                    <v-label class="text-subtitle-1 font-weight-semibold pb-2 text-lightText mt-4">Confirmar contraseña</v-label>
                                    <VTextField
                                        v-model="confirmar"
                                        :type="mostrarConfirmar ? 'text' : 'password'"
                                        :append-inner-icon="mostrarConfirmar ? 'mdi-eye-off' : 'mdi-eye'"
                                        required
                                        @click:append-inner="mostrarConfirmar = !mostrarConfirmar"
                                    ></VTextField>
                                    <v-alert v-if="error" type="error" class="mt-3 mb-3">{{ error }}</v-alert>
                                    <v-btn size="large" color="primary" block :loading="enviando" type="submit" class="mt-3" flat
                                        >Guardar contraseña</v-btn
                                    >
                                </v-form>
                            </template>

                            <template v-else>
                                <v-alert type="success" class="mb-3">Contraseña actualizada correctamente.</v-alert>
                                <v-btn size="large" color="primary" to="/" block flat>Iniciar sesión</v-btn>
                            </template>
                        </v-card-item>
                    </v-card>
                </v-col>
            </v-row>
        </v-container>
    </div>
</template>
