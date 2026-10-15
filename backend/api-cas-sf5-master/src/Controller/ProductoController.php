<?php

namespace App\Controller;

use App\Repository\Gbl\ProductoRepository;
use App\Service\ProductoGarantiaService;
use App\Service\ProductoImagenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Catálogo de producto para la creación de orden: buscador (typeahead) y
 * chequeo de garantía (punto 2 del encargo, ver ProductoGarantiaService).
 * Rutas raíz (no "/productos" repetido): el gateway ya enruta /api/cas aquí
 * (ver CasController).
 */
#[IsGranted('ROLE_CAS')]
class ProductoController extends AbstractController
{
    public function __construct(
        private readonly ProductoRepository $productos,
        private readonly ProductoGarantiaService $garantiaService,
        private readonly ProductoImagenService $productoImagen,
    ) {
    }

    #[Route('/productos/buscar', name: 'producto_buscar', methods: ['GET'])]
    public function buscar(Request $request): JsonResponse
    {
        $q = (string) $request->query->get('q', '');
        if (trim($q) === '') {
            return $this->json([]);
        }

        // Se busca una sola vez por request (no por cada producto del resultado) — ver
        // ProductoImagenService::buscarAdjuntoTipo().
        $adjuntoTipo = $this->productoImagen->buscarAdjuntoTipo();

        return $this->json(array_map(
            fn (array $producto) => $producto + ['imagenUrl' => $this->productoImagen->url($producto['item'], $adjuntoTipo)],
            $this->productos->buscarActivos($q),
        ));
    }

    /**
     * Chequeo de garantía informativo, para feedback inmediato en el
     * formulario apenas se elige el producto (y de nuevo cuando ya hay
     * fecha de factura) — la validación que realmente bloquea el guardado
     * vive en OrdenService::crear(), que usa el mismo ProductoGarantiaService.
     */
    #[Route('/productos/{id}/garantia', name: 'producto_garantia', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function garantia(int $id, Request $request): JsonResponse
    {
        $producto = $this->productos->find($id);
        if ($producto === null) {
            throw new NotFoundHttpException('Producto no encontrado.');
        }

        $fechaFacturaParam = $request->query->get('fechaFactura');
        $fechaFactura = $fechaFacturaParam ? new \DateTimeImmutable($fechaFacturaParam) : null;

        $resultado = $this->garantiaService->validar($producto, $fechaFactura);
        $resultado['fechaLimite'] = $resultado['fechaLimite']?->format('Y-m-d');

        return $this->json($resultado);
    }
}
