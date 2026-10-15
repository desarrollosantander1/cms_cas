<script setup lang="ts">
import { computed } from 'vue';
import { useDisplay } from 'vuetify';
import { useCustomizerStore } from '@/stores/customizer';
import { usePermissionsStore } from '@/stores/permissions';
import { mapBackendMenuToSidebar } from '@/utils/helpers/menuMapper';
import horizontalItems from './horizontalItems';
import NavItem from './NavItem/Index.vue';
import NavCollapse from './NavCollapse/Index.vue';
import VerticalSidebar from '../vertical-sidebar/VerticalSidebar.vue';

const customizer = useCustomizerStore();
const permissions = usePermissionsStore();
const { mdAndUp } = useDisplay();

// Misma lógica que VerticalSidebar.vue: el árbol real de módulos (ya podado
// por permiso, parte de la respuesta de /me) va primero, seguido del menú
// de demo estático de la plantilla.
const sidebarMenu = computed(() => [...mapBackendMenuToSidebar(permissions.menu), ...horizontalItems]);
// function subIsActive(input: any) {
//     const paths = Array.isArray(input) ? input : [input];
//     return paths.some((path) => {
//         return; //$route.path.indexOf(path) === 0; // current path starts with this path string
//     });
// }
</script>

<template>
    <template v-if="mdAndUp">
        <div class="horizontalMenu  border-bottom">
            <div :class="customizer.boxed ? 'maxWidth' : 'px-6'">
                <ul class="gap-1 horizontal-navbar mx-0">
                    <!---Menu Loop -->
                    <li v-for="(item, i) in sidebarMenu" :key="i" class="navItem">
                        <!---If Has Child -->
                        <NavCollapse :item="item" :level="0" v-if="item.children" />
                        <!---Single Item-->
                        <NavItem :item="item" v-else />
                        <!---End Single Item-->
                    </li>
                </ul>
            </div>    
        </div>
    </template>
    <div v-else class="mobile-menu">
        <VerticalSidebar />
    </div>
</template>
<style lang="scss"></style>
