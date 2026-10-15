<script setup lang="ts">
import { computed, ref } from 'vue';
import { UsersIcon, SwitchHorizontalIcon } from 'vue-tabler-icons';
import { useAuthStore } from '@/stores/auth';
import { usePermissionsStore } from '@/stores/permissions';
import { usersService, type UsuarioSimulable } from '@/services/usersService';

/**
 * Control de "simular usuario" para la cabecera (junto al carrito):
 * - ROLE_SIMULAR_USUARIO (evaluado sobre el usuario original, no el
 *   simulado): selector de usuarios activos, disponible siempre que se
 *   tenga el permiso, incluso ya estando en una simulación — para cambiar
 *   de usuario simulado sin volver primero al principal.
 * - Simulando: además, botón "Volver a mi usuario".
 * - Sin el permiso y sin simular: no renderiza nada.
 */
const auth = useAuthStore();
const permissions = usePermissionsStore();

const candidatos = ref<UsuarioSimulable[]>([]);
const cargando = ref(false);
const busqueda = ref('');
const aplicando = ref(false);

/**
 * Mientras se simula, el usuario simulado activo aparecería como candidato
 * (el backend solo excluye a quien autoriza la llamada, que al encadenar es
 * el original, no el simulado actual) — se filtra acá para no ofrecer
 * "simular a quien ya estás simulando".
 */
const filtrados = computed(() => {
    const q = busqueda.value.trim().toLowerCase();
    return candidatos.value
        .filter((u) => u.id !== auth.usuario?.id)
        .filter((u) => !q || [u.usuario, u.nombre, u.apellido, u.email].some((campo) => (campo ?? '').toLowerCase().includes(q)));
});

/**
 * Se muestra si el usuario original tiene el permiso — cuando ya se está
 * simulando, `permissions` refleja al simulado (normalmente sin este
 * permiso), así que `estaSimulando` alcanza como evidencia de que el
 * original sí lo tenía al empezar.
 */
const puedeSimular = computed(() => auth.estaSimulando || permissions.can('ROLE_SIMULAR_USUARIO'));

async function abrirSelector(abierto: boolean) {
    if (!abierto) return;
    cargando.value = true;
    try {
        candidatos.value = await usersService.simulables(auth.impersonadorToken ?? undefined);
    } finally {
        cargando.value = false;
    }
}

async function elegir(usuario: UsuarioSimulable) {
    if (aplicando.value) return;
    aplicando.value = true;
    try {
        await auth.simular(usuario.id);
    } finally {
        aplicando.value = false;
        busqueda.value = '';
    }
}

async function volver() {
    if (aplicando.value) return;
    aplicando.value = true;
    try {
        await auth.volverAUsuarioPrincipal();
    } finally {
        aplicando.value = false;
    }
}
</script>

<template>
    <div class="d-flex align-center ga-2">
        <v-btn
            v-if="auth.estaSimulando"
            variant="tonal"
            color="warning"
            size="small"
            class="custom-hover-primary text-none"
            :loading="aplicando"
            :disabled="aplicando"
            @click="volver"
        >
            <SwitchHorizontalIcon stroke-width="1.5" size="18" class="mr-1" />
            Volver a mi usuario
        </v-btn>

        <v-menu v-if="puedeSimular" :close-on-content-click="false" @update:model-value="abrirSelector">
            <template v-slot:activator="{ props }">
                <v-tooltip :text="auth.estaSimulando ? 'Cambiar usuario simulado' : 'Simular usuario'">
                    <template v-slot:activator="{ props: tooltipProps }">
                        <v-btn
                            icon
                            variant="text"
                            color="primary"
                            class="custom-hover-primary"
                            size="small"
                            v-bind="{ ...props, ...tooltipProps }"
                        >
                            <UsersIcon stroke-width="1.5" size="22" />
                        </v-btn>
                    </template>
                </v-tooltip>
            </template>
            <v-sheet rounded="md" width="320" elevation="10">
                <div class="px-5 pt-5 pb-3">
                    <h6 class="text-h6 mb-3">{{ auth.estaSimulando ? 'Cambiar usuario simulado' : 'Simular usuario' }}</h6>
                    <v-text-field
                        v-model="busqueda"
                        density="compact"
                        variant="outlined"
                        label="Buscar por nombre o usuario"
                        hide-details
                        autofocus
                    />
                </div>
                <perfect-scrollbar style="max-height: 320px">
                    <v-list class="py-0" lines="two" :disabled="aplicando">
                        <v-list-item v-if="cargando" title="Cargando..." />
                        <v-list-item v-else-if="filtrados.length === 0" title="Sin usuarios activos para simular." />
                        <v-list-item
                            v-for="u in filtrados"
                            :key="u.id"
                            :title="`${u.nombre ?? ''} ${u.apellido ?? ''}`.trim() || u.usuario"
                            :subtitle="u.usuario"
                            @click="elegir(u)"
                        />
                    </v-list>
                </perfect-scrollbar>
            </v-sheet>
        </v-menu>
    </div>
</template>
