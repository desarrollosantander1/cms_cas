<template>
  <CoffeeLoadingScreen v-if="isAuthenticated" :show="bootstrapping" />
  <RouterView v-if="!bootstrapping"></RouterView>
</template>

<script setup lang="ts">
import { onMounted, ref } from "vue";
import { RouterView } from "vue-router";
import CoffeeLoadingScreen from "@/components/shared/CoffeeLoadingScreen.vue";
import { useAuthStore } from "@/stores/auth";
import { usePermissionsStore } from "@/stores/permissions";

// Antes la app esperaba a rehidratar sesión y cargar permisos ANTES de
// montarse (ver main.ts), dejando la pantalla en blanco mientras tanto. Ahora
// se monta de inmediato y este loader cubre esa espera.
const bootstrapping = ref(true);
const isAuthenticated = ref(false);

onMounted(async () => {
  const authStore = useAuthStore();
  await authStore.rehydrate();
  if (authStore.isAuthenticated) {
    isAuthenticated.value = true;
    await usePermissionsStore().cargar();
  }
  bootstrapping.value = false;
});
</script>