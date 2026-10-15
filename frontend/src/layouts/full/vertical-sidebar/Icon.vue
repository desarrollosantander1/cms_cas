<script setup>
// `item` puede ser un componente de vue-tabler-icons (menú estático de la
// plantilla) o un string con un nombre de ícono MDI (ej. "mdi-cog"), tal
// como se guarda en menu_grupo.icono / menu.icono — mismo formato que ya
// usa <v-icon> en el resto de la app.
const props = defineProps({ item: [Object, String, Function], level: Number });
</script>

<template>
  <!--
    Ícono MDI como <i> plano (no <v-icon>): VListItem detecta un hijo con
    clase .v-icon en el prepend y le agrega automáticamente un spacer extra
    de 32px antes del título (ver VListItem.css), duplicando el espaciado
    que ya define .v-list-item__prepend en _sidebar.scss y corriendo el
    texto de los grupos muy a la derecha. Un <i> sin esa clase evita el
    spacer automático y queda con el mismo espaciado que los íconos SVG de
    vue-tabler-icons del menú estático.
  -->
  <i v-if="typeof item === 'string'" class="mdi iconClass" :class="item" :style="{ fontSize: (level > 0 ? '14px' : '20px') }"></i>
  <component v-else :is="item" :size="level > 0 ? 14 : 20" stroke-width="1.5" class="iconClass"></component>
</template>
