<?php

namespace App\Service;

use App\Entity\Cas;
use App\Entity\CasCliente;
use App\Repository\CasClienteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Punto 1 del encargo de creación de orden: el cliente final (cas.cas_cliente)
 * se busca por cédula DENTRO del CAS seleccionado; si existe se reutiliza (y
 * se actualiza con los datos que traiga el formulario, por si cambiaron), si
 * no existe se crea. Mismo patrón "buscar o crear" que
 * CasUsuarioService::asociar().
 */
class CasClienteService
{
    private const CAMPOS_REQUERIDOS = ['cedula', 'nombre', 'apellido', 'telefono'];

    public function __construct(
        private readonly CasClienteRepository $casClientes,
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function buscarPorCedula(Cas $cas, string $cedula): ?CasCliente
    {
        $cedula = trim($cedula);

        return $cedula === '' ? null : $this->casClientes->buscarPorCasYCedula($cas, $cedula);
    }

    /** @param array<string, mixed> $datos */
    public function encontrarOCrear(Cas $cas, array $datos): CasCliente
    {
        foreach (self::CAMPOS_REQUERIDOS as $campo) {
            if (trim((string) ($datos[$campo] ?? '')) === '') {
                throw new BadRequestHttpException(sprintf('El campo "%s" del cliente es requerido.', $campo));
            }
        }

        $cedula = trim((string) $datos['cedula']);
        $casCliente = $this->casClientes->buscarPorCasYCedula($cas, $cedula);

        if ($casCliente === null) {
            $casCliente = new CasCliente($cas, $cedula, $datos['nombre'], $datos['apellido'], $datos['telefono']);
            $this->em->persist($casCliente);
        } else {
            $casCliente->setNombre($datos['nombre']);
            $casCliente->setApellido($datos['apellido']);
            $casCliente->setTelefono($datos['telefono']);
        }

        if (array_key_exists('correo', $datos)) {
            $casCliente->setCorreo($datos['correo'] ?: null);
        }
        if (array_key_exists('direccion', $datos)) {
            $casCliente->setDireccion($datos['direccion'] ?: null);
        }

        return $casCliente;
    }
}
