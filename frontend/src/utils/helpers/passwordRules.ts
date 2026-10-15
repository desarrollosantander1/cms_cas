/**
 * Regla de fortaleza de contraseña exigida solo para usuarios del módulo
 * CAS (ver PasswordStrengthValidator en el backend "API cas sf5" — mismo
 * criterio en ambos lados). No existe en el resto del proyecto: el módulo
 * general de usuarios (UsersAdmin.vue) solo exige "no vacío".
 */
export const passwordFuerteRules = [
    (v: string) => !!v || 'La contraseña es obligatoria.',
    (v: string) => v.length >= 8 || 'Debe tener al menos 8 caracteres.',
    (v: string) => /[A-Z]/.test(v) || 'Debe incluir al menos una mayúscula.',
    (v: string) => /[a-z]/.test(v) || 'Debe incluir al menos una minúscula.',
    (v: string) => /[0-9]/.test(v) || 'Debe incluir al menos un número.',
    (v: string) => /[^A-Za-z0-9]/.test(v) || 'Debe incluir al menos un carácter especial.'
];

/** Igual que passwordFuerteRules pero sin la regla "obligatoria" — para editar un usuario existente, donde dejarla vacía significa "no cambiarla". */
export const passwordFuerteRulesOpcional = [
    (v: string) => v === '' || v.length >= 8 || 'Debe tener al menos 8 caracteres.',
    (v: string) => v === '' || /[A-Z]/.test(v) || 'Debe incluir al menos una mayúscula.',
    (v: string) => v === '' || /[a-z]/.test(v) || 'Debe incluir al menos una minúscula.',
    (v: string) => v === '' || /[0-9]/.test(v) || 'Debe incluir al menos un número.',
    (v: string) => v === '' || /[^A-Za-z0-9]/.test(v) || 'Debe incluir al menos un carácter especial.'
];
