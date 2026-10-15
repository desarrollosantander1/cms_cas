import type { Directive } from 'vue';
import { watchEffect } from 'vue';
import { usePermissionsStore } from '@/stores/permissions';

/**
 * v-can="'usuarios.crear'" — único punto donde se decide qué se oculta por
 * permiso. Nada de v-if="usuario.rol === 'admin'" repetido por componentes.
 * Oculta el elemento (no lo destruye) mientras el usuario no tenga el
 * permiso; se reevalúa solo si cambian los permisos efectivos en runtime.
 */
const stopFns = new WeakMap<HTMLElement, () => void>();

export const vCan: Directive<HTMLElement, string> = {
    mounted(el, binding) {
        const stop = watchEffect(() => {
            const permisos = usePermissionsStore();
            const permitido = permisos.can(binding.value);
            el.style.display = permitido ? '' : 'none';
        });
        stopFns.set(el, stop);
    },
    updated(el, binding) {
        // si el código de permiso cambia dinámicamente, se re-crea el watcher
        const previo = stopFns.get(el);
        previo?.();
        const stop = watchEffect(() => {
            const permisos = usePermissionsStore();
            el.style.display = permisos.can(binding.value) ? '' : 'none';
        });
        stopFns.set(el, stop);
    },
    beforeUnmount(el) {
        stopFns.get(el)?.();
        stopFns.delete(el);
    },
};
