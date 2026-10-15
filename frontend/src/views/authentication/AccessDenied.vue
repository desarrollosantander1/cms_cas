<script setup lang="ts">
import { LockAccessIcon } from 'vue-tabler-icons';
import { useRoute } from 'vue-router';

const route = useRoute();
const paginaIntentada = route.query.from as string | undefined;
</script>

<template>
    <div class="d-flex justify-center align-center text-center h-100">
        <div>
            <LockAccessIcon size="140" stroke-width="1" class="text-error" />
            <h1 class="text-h1 pt-3">403</h1>
            <h4 class="text-h4 my-8">No tienes permiso para acceder a esta página.</h4>
            <p v-if="paginaIntentada" class="text-body-1 text-medium-emphasis mb-8">
                Intentabas acceder a: <code>{{ paginaIntentada }}</code>
            </p>
            <v-btn flat color="primary" class="mb-4" to="/dashboards/modern">Volver al inicio</v-btn>
        </div>
    </div>
</template>
