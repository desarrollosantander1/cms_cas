<script setup lang="ts">
import type { MenuNodeAdmin } from '@/services/menuService';
import { PencilIcon, ShieldCheckIcon, FolderPlusIcon, EyeIcon, EyeOffIcon, TrashIcon } from 'vue-tabler-icons';

withDefaults(defineProps<{ node: MenuNodeAdmin; profundidad?: number; cambiandoEstado: Set<number> }>(), { profundidad: 0 });

defineEmits<{
    editar: [node: MenuNodeAdmin];
    roles: [node: MenuNodeAdmin];
    'agregar-hijo': [node: MenuNodeAdmin];
    'toggle-estado': [node: MenuNodeAdmin];
    eliminar: [node: MenuNodeAdmin];
}>();
</script>

<template>
    <tr>
        <td>{{ node.orden }}</td>
        <td :style="{ paddingLeft: `${16 + profundidad * 28}px` }">
            {{ node.etiqueta }}
        </td>
        <td class="text-medium-emphasis">{{ node.ruta || '—' }}</td>
        <td>
            <v-chip :color="node.estado ? 'success' : 'error'" size="small">{{ node.estado ? 'Activo' : 'Inactivo' }}</v-chip>
        </td>
        <td>
            <div class="d-flex align-center">
                <v-tooltip text="Editar">
                    <template v-slot:activator="{ props }">
                        <v-btn icon flat v-bind="props" @click="$emit('editar', node)"><PencilIcon stroke-width="1.5" size="20" class="text-primary" /></v-btn>
                    </template>
                </v-tooltip>
                <v-tooltip v-if="node.menuPadreId === null" text="Agregar submenú">
                    <template v-slot:activator="{ props }">
                        <v-btn icon flat v-bind="props" @click="$emit('agregar-hijo', node)"
                            ><FolderPlusIcon stroke-width="1.5" size="20" class="text-info"
                        /></v-btn>
                    </template>
                </v-tooltip>
                <v-tooltip text="Roles con acceso">
                    <template v-slot:activator="{ props }">
                        <v-btn icon flat v-bind="props" @click="$emit('roles', node)"><ShieldCheckIcon stroke-width="1.5" size="20" /></v-btn>
                    </template>
                </v-tooltip>
                <v-tooltip :text="node.estado ? 'Desactivar' : 'Activar'">
                    <template v-slot:activator="{ props }">
                        <v-btn
                            icon
                            flat
                            v-bind="props"
                            :loading="cambiandoEstado.has(node.id)"
                            :disabled="cambiandoEstado.has(node.id)"
                            @click="$emit('toggle-estado', node)"
                            ><EyeOffIcon v-if="node.estado" stroke-width="1.5" size="20" class="text-error" /><EyeIcon
                                v-else
                                stroke-width="1.5"
                                size="20"
                                class="text-success"
                        /></v-btn>
                    </template>
                </v-tooltip>
                <v-tooltip text="Eliminar">
                    <template v-slot:activator="{ props }">
                        <v-btn v-can="'ROLE_ELIMINAR_MENU'" icon flat v-bind="props" @click="$emit('eliminar', node)"
                            ><TrashIcon stroke-width="1.5" size="20" class="text-error"
                        /></v-btn>
                    </template>
                </v-tooltip>
            </div>
        </td>
    </tr>
    <MenuTreeNode
        v-for="hijo in node.children"
        :key="hijo.id"
        :node="hijo"
        :profundidad="profundidad + 1"
        :cambiando-estado="cambiandoEstado"
        @editar="$emit('editar', $event)"
        @roles="$emit('roles', $event)"
        @agregar-hijo="$emit('agregar-hijo', $event)"
        @toggle-estado="$emit('toggle-estado', $event)"
        @eliminar="$emit('eliminar', $event)"
    />
</template>
