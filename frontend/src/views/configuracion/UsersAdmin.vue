<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue';
import BaseBreadcrumb from '@/components/shared/BaseBreadcrumb.vue';
import { useUsersStore } from '@/stores/users';
import { usersService, type UsuarioAdmin } from '@/services/usersService';

const page = { title: 'Usuarios' };
const breadcrumbs = [
    { text: 'Configuración', disabled: true, href: '#' },
    { text: 'Aplicación', disabled: true, href: '#' },
    { text: 'Gestion usuarios', disabled: true, href: '#' },
];

const store = useUsersStore();
const search = ref('');

const items = computed(() => store.usuarios.map((u) => ({ ...u, nombreCompleto: `${u.nombre ?? ''} ${u.apellido ?? ''}`.trim() })));

const filteredItems = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return items.value;
    return items.value.filter((u) => [u.usuario, u.email, u.nombreCompleto].some((campo) => (campo ?? '').toLowerCase().includes(q)));
});

// --- paginación ---
const pagina = ref(1);
const itemsPorPagina = ref(10);
const totalPaginas = computed(() => Math.max(1, Math.ceil(filteredItems.value.length / itemsPorPagina.value)));
const itemsPaginados = computed(() => {
    const inicio = (pagina.value - 1) * itemsPorPagina.value;
    return filteredItems.value.slice(inicio, inicio + itemsPorPagina.value);
});

watch(search, () => {
    pagina.value = 1;
});

onMounted(async () => {
    await Promise.all([store.cargarUsuarios(), store.cargarRoles()]);
});

// --- crear / editar ---
const dialog = ref(false);
const dialogTab = ref('usuario');
const editando = ref<UsuarioAdmin | null>(null);
const form = reactive({ documento: '', nombre: '', apellido: '', telefono: '', usuario: '', email: '', password: '' });
const formError = ref('');
const guardando = ref(false);
const mostrarPassword = ref(false);
const rolesSeleccionados = ref<Set<number>>(new Set());
const rolesCargando = ref<Set<number>>(new Set());
const snackbar = reactive({ show: false, color: 'success', text: '' });

const formRef = ref();
const requeridoRule = (v: string) => !!v?.trim() || 'Este campo es obligatorio.';
const emailRules = [requeridoRule, (v: string) => /.+@.+\..+/.test(v) || 'Ingresa un correo válido.'];
const passwordRules = computed(() => (editando.value ? [] : [requeridoRule]));

function nuevo() {
    editando.value = null;
    Object.assign(form, { documento: '', nombre: '', apellido: '', telefono: '', usuario: '', email: '', password: '' });
    formError.value = '';
    mostrarPassword.value = false;
    rolesSeleccionados.value = new Set();
    rolesCargando.value = new Set();
    dialogTab.value = 'usuario';
    formRef.value?.resetValidation();
    dialog.value = true;
}

function editar(usuario: UsuarioAdmin) {
    editando.value = usuario;
    Object.assign(form, { ...usuario, password: '' });
    formError.value = '';
    mostrarPassword.value = false;
    rolesCargando.value = new Set();
    dialogTab.value = 'usuario';
    formRef.value?.resetValidation();
    dialog.value = true;
}

async function guardar() {
    const { valid } = await formRef.value.validate();
    if (!valid) {
        dialogTab.value = 'usuario';
        return;
    }

    guardando.value = true;
    formError.value = '';
    try {
        if (editando.value) {
            await store.actualizar(editando.value.id, {
                documento: form.documento,
                usuario: form.usuario,
                nombre: form.nombre,
                apellido: form.apellido,
                telefono: form.telefono || null,
                email: form.email,
            });
            if (form.password.trim() !== '') {
                await usersService.cambiarPassword(editando.value.id, form.password);
            }
        } else {
            const creado = await store.crear({
                documento: form.documento,
                nombre: form.nombre,
                apellido: form.apellido,
                telefono: form.telefono || null,
                usuario: form.usuario,
                email: form.email,
                password: form.password,
                politicaDatos: null,
            });
            for (const rolePermisoId of rolesSeleccionados.value) {
                await store.asignarRol(creado.id, rolePermisoId);
            }
        }
        snackbar.color = 'success';
        snackbar.text = editando.value ? 'Usuario actualizado correctamente.' : 'Usuario creado correctamente.';
        snackbar.show = true;
        dialog.value = false;
    } catch (e: any) {
        formError.value = typeof e === 'string' ? e : 'No se pudo guardar el usuario.';
        snackbar.color = 'error';
        snackbar.text = formError.value;
        snackbar.show = true;
    } finally {
        guardando.value = false;
    }
}

const usuariosCambiandoEstado = ref<Set<number>>(new Set());

async function toggleActivo(usuario: UsuarioAdmin) {
    if (usuariosCambiandoEstado.value.has(usuario.id)) return;
    usuariosCambiandoEstado.value.add(usuario.id);
    try {
        if (usuario.estado) {
            await store.desactivar(usuario.id);
        } else {
            await store.activar(usuario.id);
        }
    } finally {
        usuariosCambiandoEstado.value.delete(usuario.id);
    }
}

// --- roles (tab "Permisos" del modal de usuario) ---
function tieneRol(rolePermisoId: number): boolean {
    if (editando.value) {
        return !!editando.value.roles.some((r) => r.id === rolePermisoId);
    }
    return rolesSeleccionados.value.has(rolePermisoId);
}

async function toggleRol(rolePermisoId: number) {
    if (editando.value) {
        const nombreRol = store.roles.find((r) => r.id === rolePermisoId)?.role ?? 'rol';
        const seRemueve = tieneRol(rolePermisoId);
        rolesCargando.value.add(rolePermisoId);
        try {
            if (seRemueve) {
                await store.quitarRol(editando.value.id, rolePermisoId);
            } else {
                await store.asignarRol(editando.value.id, rolePermisoId);
            }
            editando.value = store.usuarios.find((u) => u.id === editando.value!.id) ?? null;
            snackbar.color = 'success';
            snackbar.text = seRemueve ? `Rol "${nombreRol}" removido.` : `Rol "${nombreRol}" asignado.`;
            snackbar.show = true;
        } catch (e: any) {
            snackbar.color = 'error';
            snackbar.text = typeof e === 'string' ? e : `No se pudo ${seRemueve ? 'remover' : 'asignar'} el rol "${nombreRol}".`;
            snackbar.show = true;
        } finally {
            rolesCargando.value.delete(rolePermisoId);
        }
        return;
    }
    if (rolesSeleccionados.value.has(rolePermisoId)) {
        rolesSeleccionados.value.delete(rolePermisoId);
    } else {
        rolesSeleccionados.value.add(rolePermisoId);
    }
}

/** Roles padre (rolePadreId null) con sus descendientes aplanados, para una tabla de dos columnas. */
interface RolHijo {
    id: number;
    role: string;
    profundidad: number;
}

interface RolPadreConHijos {
    id: number;
    role: string;
    hijos: RolHijo[];
}

const rolesAgrupados = computed<RolPadreConHijos[]>(() => {
    const hijosDe = new Map<number | null, typeof store.roles>();
    for (const rol of store.roles) {
        const clave = rol.rolePadreId;
        if (!hijosDe.has(clave)) hijosDe.set(clave, []);
        hijosDe.get(clave)!.push(rol);
    }

    function descendientes(padreId: number, profundidad: number): RolHijo[] {
        const resultado: RolHijo[] = [];
        for (const hijo of hijosDe.get(padreId) ?? []) {
            resultado.push({ id: hijo.id, role: hijo.role, profundidad });
            resultado.push(...descendientes(hijo.id, profundidad + 1));
        }
        return resultado;
    }

    return (hijosDe.get(null) ?? []).map((padre) => ({
        id: padre.id,
        role: padre.role,
        hijos: descendientes(padre.id, 0),
    }));
});

// --- acceso directo al tab "Permisos" del modal de edición ---
function verPermisos(usuario: UsuarioAdmin) {
    editar(usuario);
    dialogTab.value = 'permisos';
}
</script>

<template>
    <BaseBreadcrumb :title="page.title" :breadcrumbs="breadcrumbs"></BaseBreadcrumb>

    <v-card elevation="10">
        <v-card-text>
            <v-row>
                <v-col cols="12" lg="4" md="6">
                    <v-text-field density="compact" v-model="search" label="Buscar usuarios" hide-details variant="outlined"></v-text-field>
                </v-col>
                <v-col cols="12" lg="8" md="6" class="text-right">
                    <v-btn v-can="'ROLE_USUARIO_CREAR'" color="primary" flat class="ml-auto" @click="nuevo">
                        <v-icon class="mr-2">mdi-account-plus</v-icon>Nuevo usuario
                    </v-btn>
                </v-col>
            </v-row>

            <v-table class="mt-5">
                <thead>
                    <tr>
                        <th class="text-subtitle-1 font-weight-semibold">Usuario</th>
                        <th class="text-subtitle-1 font-weight-semibold">Correo</th>
                        <th class="text-subtitle-1 font-weight-semibold">Nombre</th>
                        <th class="text-subtitle-1 font-weight-semibold">Estado</th>
                        <th class="text-subtitle-1 font-weight-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody v-if="store.loading">
                    <tr v-for="n in 5" :key="`skeleton-${n}`">
                        <td><v-skeleton-loader type="text" /></td>
                        <td><v-skeleton-loader type="text" /></td>
                        <td><v-skeleton-loader type="text" /></td>
                        <td><v-skeleton-loader type="text" width="70" /></td>
                        <td><v-skeleton-loader type="text" width="120" /></td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <tr v-for="item in itemsPaginados" :key="item.id">
                        <td class="text-subtitle-1">{{ item.usuario }}</td>
                        <td class="text-subtitle-1">{{ item.email }}</td>
                        <td class="text-subtitle-1">{{ item.nombreCompleto || '—' }}</td>
                        <td>
                            <v-chip :color="item.estado ? 'success' : 'error'" size="small">{{ item.estado ? 'Activo' : 'Inactivo' }}</v-chip>
                        </td>
                        <td>
                            <div class="d-flex align-center">
                                <v-tooltip text="Editar">
                                    <template v-slot:activator="{ props }">
                                        <v-btn v-can="'ROLE_USUARIO_EDITAR'" icon flat @click="editar(item)" v-bind="props"
                                            ><PencilIcon stroke-width="1.5" size="20" class="text-primary"
                                        /></v-btn>
                                    </template>
                                </v-tooltip>

                                <v-tooltip text="Permisos efectivos">
                                    <template v-slot:activator="{ props }">
                                        <v-btn icon flat v-bind="props" @click="verPermisos(item)"
                                            ><ShieldCheckIcon stroke-width="1.5" size="20"
                                        /></v-btn>
                                    </template>
                                </v-tooltip>

                                <v-tooltip :text="item.estado ? 'Desactivar' : 'Activar'">
                                    <template v-slot:activator="{ props }">
                                        <v-btn
                                            v-can="'ROLE_USUARIO_ELIMINAR'"
                                            icon
                                            flat
                                            v-bind="props"
                                            :loading="usuariosCambiandoEstado.has(item.id)"
                                            :disabled="usuariosCambiandoEstado.has(item.id)"
                                            @click="toggleActivo(item)"
                                            ><UserOffIcon v-if="item.estado" stroke-width="1.5" size="20" class="text-error" /><UserCheckIcon
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
                    <tr v-if="filteredItems.length === 0">
                        <td colspan="5" class="text-center text-medium-emphasis py-6">Sin resultados.</td>
                    </tr>
                </tbody>
            </v-table>

            <div v-if="filteredItems.length > 0" class="d-flex justify-center mt-3">
                <v-pagination v-model="pagina" :length="totalPaginas" :total-visible="7"></v-pagination>
            </div>
        </v-card-text>
    </v-card>

    <!-- crear / editar -->
    <v-dialog v-model="dialog" max-width="760px" scrollable>
        <v-card>
            <v-card-title class="pa-4 bg-secondary">
                <span class="text-h5">{{ editando ? 'Editar usuario' : 'Nuevo usuario' }}</span>
            </v-card-title>
            <v-tabs v-model="dialogTab" color="primary" class="flex-shrink-0">
                <v-tab value="usuario">Usuario</v-tab>
                <v-tab value="permisos">Permisos</v-tab>
            </v-tabs>
            <v-card-text>
                <v-window v-model="dialogTab">
                    <v-window-item value="usuario">
                        <v-form ref="formRef">
                            <v-row class="mt-1">
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="form.documento" label="Documento" :rules="[requeridoRule]" />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="form.usuario" label="Usuario" :rules="[requeridoRule]" />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="form.nombre" label="Nombre" :rules="[requeridoRule]" />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="form.apellido" label="Apellido" :rules="[requeridoRule]" />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="form.telefono" label="Teléfono" />
                                </v-col>
                                <v-col cols="12" sm="6">
                                    <v-text-field v-model="form.email" label="Email" :rules="emailRules" />
                                </v-col>
                                <v-col cols="12">
                                    <v-text-field
                                        v-model="form.password"
                                        :label="editando ? 'Nueva contraseña' : 'Contraseña'"
                                        :hint="editando ? 'Dejar en blanco para no modificarla' : ''"
                                        :persistent-hint="!!editando"
                                        :rules="passwordRules"
                                        :type="mostrarPassword ? 'text' : 'password'"
                                        :append-inner-icon="mostrarPassword ? 'mdi-eye-off' : 'mdi-eye'"
                                        @click:append-inner="mostrarPassword = !mostrarPassword"
                                    />
                                </v-col>
                            </v-row>
                        </v-form>
                    </v-window-item>

                    <v-window-item value="permisos">
                        <v-table density="compact" class="mt-1">
                            <thead>
                                <tr>
                                    <th class="w-50">Rol padre</th>
                                    <th class="w-50">Roles hijos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="padre in rolesAgrupados" :key="padre.id">
                                    <td class="align-top pt-2">
                                        <v-checkbox
                                            :model-value="tieneRol(padre.id)"
                                            :label="padre.role"
                                            hide-details
                                            density="compact"
                                            class="font-weight-bold"
                                            :loading="rolesCargando.has(padre.id)"
                                            :disabled="rolesCargando.has(padre.id)"
                                            @update:model-value="toggleRol(padre.id)"
                                        />
                                    </td>
                                    <td class="align-top pt-2">
                                        <span v-if="padre.hijos.length === 0" class="text-caption text-medium-emphasis">Sin roles hijos</span>
                                        <div
                                            v-for="hijo in padre.hijos"
                                            :key="hijo.id"
                                            :style="{ paddingLeft: `${hijo.profundidad * 24}px` }"
                                        >
                                            <v-checkbox
                                                :model-value="tieneRol(hijo.id)"
                                                :label="hijo.role"
                                                hide-details
                                                density="compact"
                                                :loading="rolesCargando.has(hijo.id)"
                                                :disabled="rolesCargando.has(hijo.id)"
                                                @update:model-value="toggleRol(hijo.id)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </v-table>
                    </v-window-item>
                </v-window>
                <v-alert v-if="formError" type="error" density="compact" class="mt-3">{{ formError }}</v-alert>
            </v-card-text>
            <v-card-actions>
                <v-spacer />
                <v-btn color="error" variant="flat" :disabled="guardando || rolesCargando.size > 0" @click="dialog = false">Cancelar</v-btn>
                <v-btn
                    color="success"
                    variant="flat"
                    :loading="guardando"
                    :disabled="guardando || rolesCargando.size > 0"
                    @click="guardar"
                    >Guardar</v-btn
                >
            </v-card-actions>
        </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color" location="top right" timeout="4000">
        {{ snackbar.text }}
    </v-snackbar>
</template>

<style scoped>
/* Dentro del diálogo (v-card en flex column), .v-tabs es un flex-item que
   por defecto puede encogerse por debajo de su alto natural (48px) cuando el
   contenido de la tab activa es alto — eso recorta el slider/subrayado del
   tab seleccionado (queda al ras del borde inferior) y hace imposible ver
   cuál tab está activo. flex-shrink-0 lo fija en su alto natural siempre. */
:deep(.v-dialog .v-tabs) {
    flex-shrink: 0;
}
</style>
