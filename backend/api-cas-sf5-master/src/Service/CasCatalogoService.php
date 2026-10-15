<?php

namespace App\Service;

use App\Entity\Gbl\Cliente;
use App\Entity\Gbl\PaisDptoCiudad;
use App\Repository\Gbl\ClienteRepository;
use App\Repository\Gbl\ClienteSucursalRepository;
use App\Repository\Gbl\PaisDptoCiudadRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Catálogos de referencia para el modal de creación/edición de CAS:
 * clientes y sus sucursales (ambos espejo de solo lectura de datacredito) y
 * ciudades (espejo de solo lectura, usado cuando el CAS no tiene sucursal).
 */
class CasCatalogoService
{
    public function __construct(
        private readonly ClienteRepository $clientes,
        private readonly ClienteSucursalRepository $clienteSucursales,
        private readonly PaisDptoCiudadRepository $ciudades,
    ) {
    }

    /** @return Cliente[] */
    public function buscarClientes(string $q): array
    {
        return trim($q) === '' ? [] : $this->clientes->buscar($q);
    }

    /** @return \App\Entity\Gbl\ClienteSucursal[] */
    public function sucursalesDeCliente(int $clienteId): array
    {
        $cliente = $this->clientes->find($clienteId);
        if ($cliente === null) {
            throw new NotFoundHttpException('Cliente no encontrado.');
        }

        return $this->clienteSucursales->findBy(['cliente' => $cliente, 'estado' => true], ['nombre' => 'ASC']);
    }

    /** @return PaisDptoCiudad[] */
    public function listarCiudades(): array
    {
        return $this->ciudades->listar();
    }
}
