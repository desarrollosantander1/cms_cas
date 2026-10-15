<?php

namespace App\Security;

/**
 * Verifica si el portador de un JWT tiene un permiso (código de role_permiso)
 * efectivo. API auth sf4-1 no tiene esa información localmente — solo emite
 * tokens con roles: ['ROLE_USER'] a propósito — así que la implementación
 * real le pregunta a API users sf4-1, dueño de la jerarquía de permisos.
 */
interface RemotePermissionCheckerInterface
{
    public function tienePermiso(string $bearerToken, string $role): bool;
}
