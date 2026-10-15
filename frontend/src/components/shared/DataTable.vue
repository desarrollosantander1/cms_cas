<script setup lang="ts">
withDefaults(
    defineProps<{
        title: string;
        headers: any[];
        items: any[];
        loading?: boolean;
        search?: string;
    }>(),
    { loading: false, search: '' },
);
</script>

<template>
    <v-data-table class="border rounded-md" :headers="headers" :items="items" :loading="loading" :search="search">
        <template v-slot:top>
            <v-toolbar class="bg-lightprimary" flat>
                <v-toolbar-title>{{ title }}</v-toolbar-title>
                <v-divider class="mx-4" inset vertical></v-divider>
                <v-spacer></v-spacer>
                <slot name="toolbar-actions"></slot>
            </v-toolbar>
        </template>

        <!-- Reenvía cualquier slot con nombre (item.actions, item.activo, etc.)
             que el consumidor quiera personalizar, sin que este componente
             tenga que conocerlos de antemano. -->
        <template v-for="(_, slotName) in $slots" v-slot:[slotName]="scope">
            <slot v-if="slotName !== 'toolbar-actions'" :name="slotName" v-bind="scope ?? {}"></slot>
        </template>
    </v-data-table>
</template>
