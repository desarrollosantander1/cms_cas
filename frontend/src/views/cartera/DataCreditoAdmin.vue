<script setup lang="ts">
import { ref } from 'vue';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import DataCreditoTab from './datacredito/DataCreditoTab.vue';
import ReclasificadosTab from './datacredito/ReclasificadosTab.vue';
import { usePermissionsStore } from '@/stores/permissions';

const page = { title: 'Datacrédito' };
const breadcrumbs = [
    { text: 'Cartera', disabled: true, href: '#' },
    { text: 'Gestión cartera', disabled: true, href: '#' },
    { text: 'Datacrédito', disabled: true, href: '#' }
];

const permissions = usePermissionsStore();

// El tab inicial debe ser uno al que el usuario realmente tenga acceso —
// si arranca fijo en 'datacredito', ese v-window-item se activa (y monta,
// disparando sus propias peticiones) antes de que v-can lo oculte, incluso
// para un usuario que solo tiene ROLE_RECLASIFICADO_VER. v-can solo oculta
// visualmente (ver directives/can.ts), no evita el montaje — por eso el
// valor inicial de `tab` es lo que realmente decide qué se monta primero.
const tab = ref(permissions.can('ROLE_DATACREDITO_VER') ? 'datacredito' : 'reclasificados');
</script>

<template>
    <BaseBreadcrumb :title="page.title" :breadcrumbs="breadcrumbs"></BaseBreadcrumb>

    <v-card elevation="10">
        <!--
            v-if en vez de v-can a propósito: v-can solo hace
            el.style.display = '' cuando el permiso pasa (ver
            directives/can.ts), sin saber nada de si ESE tab es el activo
            para v-window — cuando el usuario tiene AMBOS roles, v-can pone
            display:'' en los dos v-window-item sin importar cuál está
            seleccionado, así que Vuetify termina mostrando el contenido de
            los dos tabs superpuesto. v-if excluye el tab sin permiso del
            árbol de v-window por completo, dejando que v-window maneje su
            propia visibilidad/transición sin interferencia.
        -->
        <v-tabs v-model="tab" color="primary">
            <v-tab v-if="permissions.can('ROLE_DATACREDITO_VER')" value="datacredito"><v-icon start>mdi-credit-card-outline</v-icon>Datacrédito</v-tab>
            <v-tab v-if="permissions.can('ROLE_RECLASIFICADO_VER')" value="reclasificados"><v-icon start>mdi-swap-horizontal</v-icon>Reclasificados</v-tab>
        </v-tabs>
        <v-card-text>
            <v-window v-model="tab">
                <v-window-item v-if="permissions.can('ROLE_DATACREDITO_VER')" value="datacredito">
                    <DataCreditoTab />
                </v-window-item>
                <v-window-item v-if="permissions.can('ROLE_RECLASIFICADO_VER')" value="reclasificados">
                    <ReclasificadosTab />
                </v-window-item>
            </v-window>
        </v-card-text>
    </v-card>
</template>
