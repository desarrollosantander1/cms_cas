<script setup lang="ts">
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { Form } from 'vee-validate';

/*Social icons*/
import google from '@/assets/images/svgs/google-icon.svg';
import facebook from '@/assets/images/svgs/facebook-icon.svg';

const checkbox = ref(false);
const valid = ref(false);
const show1 = ref(false);
const username = ref('');
const password = ref('');
const passwordRules = ref([(v: string) => !!v || 'Contraseña obligatoria']);
const emailRules = ref([(v: string) => !!v || 'Usuario obligatorio']);

function validate(values: any, { setErrors }: any) {
    const authStore = useAuthStore();
    // Mensaje genérico siempre: el backend no distingue usuario inexistente
    // de contraseña incorrecta, y el formulario tampoco debe hacerlo.
    return authStore
        .login(username.value, password.value)
        .catch(() => setErrors({ apiError: 'Usuario o contraseña incorrectos.' }));
}
</script>

<template>
    <!-- <v-row class="d-flex mb-3">
        <v-col cols="6" sm="6" >
            <v-btn variant="outlined" size="large" class="border text-subtitle-1" block>
                <img :src="google" height="16" class="mr-2" alt="google" />
                <span class="d-sm-flex d-none mr-1">Sign in with</span>Google
            </v-btn>
        </v-col>
        <v-col cols="6" sm="6">
            <v-btn variant="outlined" size="large" class="border text-subtitle-1" block>
                <img :src="facebook" width="25" height="25" class="mr-1" alt="facebook" />
                <span class="d-sm-flex d-none mr-1">Sign in with</span>FB
            </v-btn>
        </v-col>
    </v-row> -->
    <div class="d-flex align-center text-center mb-6">
        <div class="text-h6 w-100 px-5 font-weight-regular auth-divider position-relative">
            <span class="bg-surface px-5 py-3 position-relative">Iniciar sesión</span>
        </div>  
    </div>
    <Form @submit="validate" v-slot="{ errors, isSubmitting }" class="mt-5">
        <v-label class="text-subtitle-1 font-weight-semibold pb-2 text-lightText">Usuario</v-label>
        <VTextField
            v-model="username"
            :rules="emailRules"
            class="mb-8"
            required
            hide-details="auto"
            placeholder="Ingrese su usuario"
        ></VTextField>
        <v-label class="text-subtitle-1 font-weight-semibold pb-2 text-lightText">Contraseña</v-label>
        <VTextField
            v-model="password"
            :rules="passwordRules"
            required
            hide-details="auto"
            :type="show1 ? 'text' : 'password'"
            :append-inner-icon="show1 ? 'mdi-eye-off' : 'mdi-eye'"
            class="pwdInput"
            placeholder="Ingrese su contraseña"
            @click:append-inner="show1 = !show1"
        ></VTextField>
        <div class="d-flex flex-wrap align-center my-3 ml-n2">
            <!-- <v-checkbox v-model="checkbox" :rules="[(v:any) => !!v || 'You must agree to continue!']" required hide-details color="primary">
                <template v-slot:label class="">Recuérdeme</template>
            </v-checkbox> -->
            <div class="ml-sm-auto">
                <RouterLink to="/auth/forgot-password2" class="text-primary text-decoration-none text-body-1 opacity-1 font-weight-medium">
                    ¿Olvidaste tu contraseña?
                </RouterLink>
            </div>
        </div>
        <v-btn size="large" :loading="isSubmitting" color="primary" :disabled="valid" block type="submit" flat>Entrar</v-btn>
        <div v-if="errors.apiError" class="mt-2">
            <v-alert color="error">{{ errors.apiError }}</v-alert>
        </div>
    </Form>
</template>
