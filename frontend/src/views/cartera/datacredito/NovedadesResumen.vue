<script setup lang="ts">
import type { ResumenNovedades } from '@/types/dataCredito';

defineProps<{ resumen: ResumenNovedades }>();
</script>

<template>
    <div v-if="resumen.totalNovedades > 0" class="d-flex flex-wrap align-center ga-2 mt-3">
        <v-chip color="info" variant="flat"><strong>Cantidad novedades:</strong>&nbsp;{{ resumen.totalRegistros }}</v-chip>
        <v-chip color="info" variant="flat"><strong>Suma novedades:</strong>&nbsp;{{ resumen.totalNovedades }}</v-chip>
        <v-chip v-for="item in resumen.resumen" :key="item.novedad" color="info" variant="tonal">
            N{{ item.novedad }} : {{ item.cantidad }}
        </v-chip>
    </div>
</template>
