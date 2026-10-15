<?php

namespace App\Controller;

use App\Entity\Gbl\Cliente;
use App\Entity\Gbl\ClienteSucursal;
use App\Entity\Gbl\PaisDptoCiudad;
use App\Service\CasCatalogoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Catálogos de referencia del modal de creación/edición de CAS: cliente ->
 * sucursal (cascada opcional) y ciudad (cuando no hay sucursal). Rutas raíz
 * (no "/cas/clientes"): el gateway ya enruta /api/cas aquí (ver CasController).
 */
#[IsGranted('ROLE_CAS_ADMIN')]
class CasCatalogoController extends AbstractController
{
    public function __construct(private readonly CasCatalogoService $catalogo)
    {
    }

    #[Route('/clientes', name: 'cas_clientes_buscar', methods: ['GET'])]
    public function clientes(Request $request): JsonResponse
    {
        $q = (string) $request->query->get('q', '');
        $items = array_map(
            fn (Cliente $c) => ['id' => $c->getId(), 'numeroIdentidad' => $c->getNumeroIdentidad(), 'nombre' => $c->getNombreMostrar()],
            $this->catalogo->buscarClientes($q),
        );

        return $this->json($items);
    }

    #[Route('/clientes/{id}/sucursales', name: 'cas_sucursales_de_cliente', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function sucursales(int $id): JsonResponse
    {
        $items = array_map(
            fn (ClienteSucursal $s) => [
                'id' => $s->getId(),
                'nombre' => $s->getNombre(),
                'direccion' => $s->getDireccion(),
                'telefono' => $s->getTelefono(),
                // Para prellenar (de solo lectura) el select de Ciudad del
                // modal cuando se elige esta sucursal — ver CasFormModal.vue.
                'ciudadId' => $s->getPaisDptoCiudad()?->getId(),
                'ciudadNombre' => $s->getCiudadNombre(),
            ],
            $this->catalogo->sucursalesDeCliente($id),
        );

        return $this->json($items);
    }

    #[Route('/ciudades', name: 'cas_ciudades_listar', methods: ['GET'])]
    public function ciudades(): JsonResponse
    {
        $items = array_map(
            fn (PaisDptoCiudad $c) => [
                'id' => $c->getId(),
                'nombre' => $c->getNombre(),
                'departamento' => $c->getPaisDpto()->getNombre(),
                'pais' => $c->getPaisDpto()->getPais()->getNombre(),
            ],
            $this->catalogo->listarCiudades(),
        );

        return $this->json($items);
    }
}
