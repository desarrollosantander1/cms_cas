<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Regla de fortaleza de contraseña exigida solo para usuarios del módulo CAS
 * (ver punto 77 del encargo original): mínimo 8 caracteres, al menos una
 * mayúscula, una minúscula, un número y un carácter especial. No existe en
 * ningún otro lugar del proyecto (el módulo general de usuarios solo exige
 * "no vacío" — ver UsuarioService::cambiarPassword() en API users sf4-1);
 * se implementa aquí desde cero, acotada a este alcance.
 */
class PasswordStrengthValidator
{
    public function validar(string $password): void
    {
        $errores = [];

        if (mb_strlen($password) < 8) {
            $errores[] = 'al menos 8 caracteres';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errores[] = 'una letra mayúscula';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errores[] = 'una letra minúscula';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errores[] = 'un número';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errores[] = 'un carácter especial';
        }

        if ($errores !== []) {
            throw new BadRequestHttpException('La contraseña debe tener '.implode(', ', $errores).'.');
        }
    }
}
