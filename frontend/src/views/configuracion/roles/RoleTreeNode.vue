<script setup lang="ts">
import type { RoleNodo } from '../RolesAdmin.vue';
import { PencilIcon, FolderPlusIcon, EyeIcon, EyeOffIcon } from 'vue-tabler-icons';

const NIVEL_MAXIMO = 3;

withDefaults(defineProps<{ node: RoleNodo; profundidad?: number; cambiandoEstado: Set<number> }>(), { profundidad: 0 });

defineEmits<{
    editar: [node: RoleNodo];
    'agregar-hijo': [node: RoleNodo];
    'toggle-estado': [node: RoleNodo];
}>();
</script>

<template>
    <tr>
        <td :style="{ paddingLeft: `${16 + profundidad * 28}px` }">
            {{ node.role }}
        </td>
        <td>
            <v-chip size="small" variant="tonal">Nivel {{ node.nivel }}</v-chip>
        </td>
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
                <v-tooltip v-if="node.nivel < NIVEL_MAXIMO" text="Agregar rol hijo">
                    <template v-slot:activator="{ props }">
                        <v-btn icon flat v-bind="props" @click="$emit('agregar-hijo', node)"
                            ><FolderPlusIcon stroke-width="1.5" size="20" class="text-info"
                        /></v-btn>
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
            </div>
        </td>
    </tr>
    <RoleTreeNode
        v-for="hijo in node.hijos"
        :key="hijo.id"
        :node="hijo"
        :profundidad="profundidad + 1"
        :cambiando-estado="cambiandoEstado"
        @editar="$emit('editar', $event)"
        @agregar-hijo="$emit('agregar-hijo', $event)"
        @toggle-estado="$emit('toggle-estado', $event)"
    />
</template>
