<script setup lang="ts">
import { computed } from 'vue';
import { useCustomizerStore } from '@/stores/customizer';
import { usePermissionsStore } from '@/stores/permissions';
import { mapBackendMenuToSidebar } from '@/utils/helpers/menuMapper';
import sidebarItems from './sidebarItem';

import NavGroup from './NavGroup/index.vue';
import NavItem from './NavItem/index.vue';
import NavCollapse from './NavCollapse/NavCollapse.vue';
import Profile from './profile/Profile.vue';
import Logo from '../logo/Logo.vue';
import RtlLogo from '../logo/RtlLogo.vue';

const customizer = useCustomizerStore();
const permissions = usePermissionsStore();

// El árbol real de módulos (ya podado por permiso, parte de la respuesta de /me) va primero,
// seguido del menú de demo estático de la plantilla — no tiene sentido
// llevar eCommerce/Charts/Front Pages a base de datos.
const sidebarMenu = computed(() => [...mapBackendMenuToSidebar(permissions.menu), ...sidebarItems]);
</script>

<template>
    <v-navigation-drawer
        left
        v-model="customizer.Sidebar_drawer"
        elevation="0"
        rail-width="75"
        app
        class="leftSidebar"
        :rail="customizer.mini_sidebar"
        expand-on-hover width="270"
    >
        <!---Logo part -->
        <v-locale-provider  v-if="customizer.setRTLLayout"  rtl >
        <div class="px-5 py-2 logoBar">
            <RtlLogo />
        </div>
        </v-locale-provider>
        <v-locale-provider  v-else>
        <div class="px-5 py-2 logoBar">
            <Logo />
        </div>
        </v-locale-provider>

        <!-- ---------------------------------------------- -->
        <!---Navigation -->
        <!-- ---------------------------------------------- -->
        <perfect-scrollbar class="scrollnavbar">
            <v-list class="pa-6">
                <!---Menu Loop -->
                <template v-for="(item, i) in sidebarMenu">
                    <!---Item Sub Header -->
                    <NavGroup :item="item" v-if="item.header" :key="item.title" />
                    <!---If Has Child -->
                    <NavCollapse class="leftPadding" :item="item" :level="0" v-else-if="item.children" />
                    <!---Single Item-->
                    <NavItem :item="item" v-else class="leftPadding" />
                    <!---End Single Item-->
                </template>
            </v-list>
            <div class="pa-6 userbottom">
                <Profile />
            </div>
        </perfect-scrollbar>
    </v-navigation-drawer>
</template>

<style scoped>
.logoBar {
    border-bottom: 1px solid rgba(var(--v-border-color), var(--v-border-opacity));
}

/* El ícono queda pegado arriba de su propio cuadro dentro del SVG (mismo
   motivo que en HorizontalHeader.vue), así que el logo se ve "alto" con un
   hueco grande antes de la línea divisoria. Se empuja solo el contenido, no
   el div (que es quien dibuja esa línea), para no correr el borde. */
.logoBar :deep(.logo) {
    display: block;
    transform: translateY(8px);
}
</style>
