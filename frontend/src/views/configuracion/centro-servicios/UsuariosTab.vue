<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useCasStore } from '@/stores/cas';

const store = useCasStore();
const search = ref('');

const filteredItems = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return store.casUsuariosList;
    return store.casUsuariosList.filter((r) =>
        [r.usuario, r.documento, r.nombre, r.casNit, r.casNombre].some((campo) => (campo ?? '').toLowerCase().includes(q))
    );
});

onMounted(() => {
    store.cargarUsuariosTodos();
});
</script>

<template>
    <!-- pt-3: mismo bug de recorte del label flotante por overflow:hidden del v-window — ver CasTab.vue. -->
    <v-row class="pt-3">
        <v-col cols="12" lg="4" md="6">
            <v-text-field density="compact" v-model="search" label="Buscar usuario, documento o CAS" hide-details variant="outlined" />
        </v-col>
    </v-row>

    <v-table class="mt-5">
        <thead>
            <tr>
                <th class="text-subtitle-1 font-weight-semibold">Usuario</th>
                <th class="text-subtitle-1 font-weight-semibold">Documento</th>
                <th class="text-subtitle-1 font-weight-semibold">Nombre</th>
                <th class="text-subtitle-1 font-weight-semibold">Estado</th>
                <th class="text-subtitle-1 font-weight-semibold">Creado</th>
                <th class="text-subtitle-1 font-weight-semibold">NIT CAS</th>
                <th class="text-subtitle-1 font-weight-semibold">CAS</th>
            </tr>
        </thead>
        <tbody v-if="store.loadingUsuarios">
            <tr v-for="n in 7" :key="`skeleton-${n}`">
                <td><v-skeleton-loader type="text" /></td>
                <td><v-skeleton-loader type="text" /></td>
                <td><v-skeleton-loader type="text" /></td>
                <td><v-skeleton-loader type="text" /></td>
                <td><v-skeleton-loader type="text" /></td>
                <td><v-skeleton-loader type="text" /></td>
                <td><v-skeleton-loader type="text" /></td>
            </tr>
        </tbody>
        <tbody v-else>
            <tr v-for="item in filteredItems" :key="item.casUsuarioId">
                <td class="text-subtitle-1">
                    <v-tooltip :text="`usuario.id: ${item.usuarioId} · cas_usuario.id: ${item.casUsuarioId}`">
                        <template v-slot:activator="{ props }">
                            <span v-bind="props">{{ item.usuario }}</span>
                        </template>
                    </v-tooltip>
                </td>
                <td class="text-subtitle-1">{{ item.documento ?? '—' }}</td>
                <td class="text-subtitle-1">{{ item.nombre || '—' }}</td>
                <td class="text-subtitle-1">
                    <v-chip :color="item.casUsuarioEstado ? 'success' : 'error'" size="small">{{ item.casUsuarioEstado ? 'Activo' : 'Inactivo' }}</v-chip>
                </td>
                <td class="text-subtitle-1">{{ new Date(item.casUsuarioCreadoEn).toLocaleDateString() }}</td>
                <td class="text-subtitle-1">{{ item.casNit ?? '—' }}</td>
                <td class="text-subtitle-1">{{ item.casId }} - {{ item.casNombre ?? '—' }}</td>
            </tr>
            <tr v-if="filteredItems.length === 0">
                <td colspan="5" class="text-center text-medium-emphasis py-6">Sin resultados.</td>
            </tr>
        </tbody>
    </v-table>
</template>
