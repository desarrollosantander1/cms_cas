<script setup lang="ts">
import { ref } from 'vue';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import CasTab from './centro-servicios/CasTab.vue';
import UsuariosTab from './centro-servicios/UsuariosTab.vue';

const page = { title: 'Centros de servicio' };
const breadcrumbs = [
    { text: 'Configuración', disabled: true, href: '#' },
    { text: 'Calidad', disabled: true, href: '#' },
    { text: 'Centros de servicio', disabled: true, href: '#' }
];

// Ambos tabs requieren el mismo ROLE_CAS_ADMIN (ver guard de ruta), así que
// a diferencia de DataCreditoAdmin.vue no hace falta calcular el tab inicial
// según permisos — siempre arranca en "cas".
const tab = ref('cas');
</script>

<template>
    <BaseBreadcrumb :title="page.title" :breadcrumbs="breadcrumbs"></BaseBreadcrumb>

    <v-card elevation="10">
        <v-tabs v-model="tab" color="primary">
            <v-tab value="cas"><v-icon start>mdi-store-outline</v-icon>CAS</v-tab>
            <v-tab value="usuarios"><v-icon start>mdi-account-group-outline</v-icon>Usuarios</v-tab>
        </v-tabs>
        <v-card-text>
            <v-window v-model="tab">
                <v-window-item value="cas">
                    <CasTab />
                </v-window-item>
                <v-window-item value="usuarios">
                    <UsuariosTab />
                </v-window-item>
            </v-window>
        </v-card-text>
    </v-card>
</template>
