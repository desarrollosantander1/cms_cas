<?php

namespace App\Controller\GeneradorCartas;

use App\Entity\Gbl\Usuario;
use App\Service\EmpleadoActualResolver;
use App\Service\GeneradorCartas\GeneradorCartaService;
use App\Service\GeneradorCartas\TasaInteresService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Generador de Cartas de cartera (v1: cartas jurídicas en PDF).
 *
 * El prefijo público /api/cartera/generador-cartas llega intacto desde el
 * gateway (strip_prefix: false) para no colisionar con el /clientes del flujo
 * DataCrédito (ClienteReporteController). security.yaml exige autenticación
 * para este prefijo pese a caer bajo ^/api.
 */
#[Route('/api/cartera/generador-cartas')]
#[IsGranted('ROLE_GENERADOR_CARTAS')]
class GeneradorCartasController extends AbstractController
{
    public function __construct(
        private readonly GeneradorCartaService $generador,
        private readonly TasaInteresService $tasaInteres,
        private readonly EmpleadoActualResolver $empleadoActual,
    ) {
    }

    #[Route('/tasa', name: 'generador_cartas_tasa_ver', methods: ['GET'])]
    public function verTasa(): JsonResponse
    {
        return $this->json($this->tasaInteres->estadoVigente());
    }

    #[Route('/tasa', name: 'generador_cartas_tasa_actualizar', methods: ['PUT'])]
    #[IsGranted('ROLE_GENERADOR_CARTAS_TASA')]
    public function actualizarTasa(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        if (!isset($payload['tasaMensual']) || !is_numeric($payload['tasaMensual'])) {
            throw new BadRequestHttpException('El campo "tasaMensual" es requerido y debe ser numérico.');
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        return $this->json($this->tasaInteres->actualizar(
            (float) $payload['tasaMensual'],
            $this->empleadoActual->resolver($usuario),
        ));
    }

    #[Route('/clientes', name: 'generador_cartas_clientes', methods: ['GET'])]
    public function buscarClientes(Request $request): JsonResponse
    {
        return $this->json($this->generador->buscarClientes((string) $request->query->get('q', '')));
    }

    #[Route('/clientes/{nit}/facturas', name: 'generador_cartas_facturas', methods: ['GET'])]
    public function facturasCliente(string $nit): JsonResponse
    {
        return $this->json($this->generador->listarFacturasConSaldo($nit));
    }

    #[Route('/pdf', name: 'generador_cartas_pdf', methods: ['POST'])]
    public function pdf(Request $request): Response
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        foreach (['tipo', 'nit', 'facturas'] as $campo) {
            if (!isset($payload[$campo])) {
                throw new BadRequestHttpException(sprintf('El campo "%s" es requerido.', $campo));
            }
        }
        if (!\is_array($payload['facturas'])) {
            throw new BadRequestHttpException('El campo "facturas" debe ser un arreglo de comprobantes.');
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        $paramsDays = isset($payload['paramsDays']) && $payload['paramsDays'] !== null
            ? (int) $payload['paramsDays']
            : null;
        $tasaMensualOverride = $this->tasaMensualOverrideDe($payload);

        $pdf = $this->generador->generarPdf(
            (string) $payload['tipo'],
            (string) $payload['nit'],
            $payload['facturas'],
            $paramsDays,
            $usuario->getEmail(),
            $tasaMensualOverride,
        );

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $this->generador->nombreArchivo((string) $payload['tipo'])),
        ]);
    }

    #[Route('/email', name: 'generador_cartas_email', methods: ['POST'])]
    public function email(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        foreach (['tipo', 'nit', 'facturas', 'destinatario'] as $campo) {
            if (!isset($payload[$campo])) {
                throw new BadRequestHttpException(sprintf('El campo "%s" es requerido.', $campo));
            }
        }
        if (!\is_array($payload['facturas'])) {
            throw new BadRequestHttpException('El campo "facturas" debe ser un arreglo de comprobantes.');
        }

        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        $paramsDays = isset($payload['paramsDays']) && $payload['paramsDays'] !== null
            ? (int) $payload['paramsDays']
            : null;
        $tasaMensualOverride = $this->tasaMensualOverrideDe($payload);

        $this->generador->enviarPorCorreo(
            (string) $payload['tipo'],
            (string) $payload['nit'],
            $payload['facturas'],
            $paramsDays,
            $usuario->getEmail(),
            (string) $payload['destinatario'],
            $tasaMensualOverride,
        );

        return $this->json(['message' => 'La carta se envió correctamente.']);
    }

    /**
     * Tasa ajustada por el usuario en el frontend para esta generación (solo
     * de sesión, no persistida en BD) — ver TasaInteresService y el store
     * `generadorCartasStore` del frontend.
     *
     * @param array<string, mixed> $payload
     */
    private function tasaMensualOverrideDe(array $payload): ?float
    {
        return isset($payload['tasaMensual']) && is_numeric($payload['tasaMensual'])
            ? (float) $payload['tasaMensual']
            : null;
    }
}
