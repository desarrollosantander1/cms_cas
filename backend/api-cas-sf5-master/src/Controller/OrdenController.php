<?php

namespace App\Controller;

use App\Entity\Cas;
use App\Entity\CasCliente;
use App\Entity\Gbl\Usuario;
use App\Entity\Orden;
use App\Entity\OrdenAdjunto;
use App\Repository\CasUsuarioRepository;
use App\Repository\OrdenAdjuntoRepository;
use App\Repository\OrdenRepository;
use App\Service\ArchivoAdjuntoUploader;
use App\Service\CasClienteService;
use App\Service\CasService;
use App\Service\OrdenMailerService;
use App\Service\OrdenPdfService;
use App\Service\OrdenService;
use App\Service\ProductoImagenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Creación de orden de servicio (Fase 3, alcance inicial: solo creación).
 * `#[IsGranted('ROLE_CAS')]`, no ROLE_CAS_ADMIN — cualquier operativo de un
 * CAS crea órdenes, por eso además se verifica en cada acción que el usuario
 * autenticado esté asociado a ESE CAS puntual (no basta con tener el rol
 * global): sin ese chequeo, cualquier usuario con ROLE_CAS podría leer
 * clientes o crear órdenes en un CAS ajeno solo adivinando el id.
 */
#[IsGranted('ROLE_CAS')]
class OrdenController extends AbstractController
{
    public function __construct(
        private readonly CasService $casService,
        private readonly CasUsuarioRepository $casUsuarios,
        private readonly CasClienteService $casClienteService,
        private readonly OrdenService $ordenService,
        private readonly OrdenRepository $ordenes,
        private readonly OrdenAdjuntoRepository $ordenAdjuntos,
        private readonly ArchivoAdjuntoUploader $uploader,
        private readonly OrdenMailerService $ordenMailer,
        private readonly OrdenPdfService $ordenPdf,
        private readonly ProductoImagenService $productoImagen,
    ) {
    }

    /**
     * Listado "plano" para la tabla de /cas/ordenes — ver OrdenRepository::listarPorCas() (SQL
     * nativo: cruza 4 tablas solo para mostrar campos ya concatenados, no hace falta hidratar
     * entidades completas aquí).
     */
    #[Route('/{id}/ordenes', name: 'orden_listar', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function listar(int $id): JsonResponse
    {
        $cas = $this->casYUsuarioAutorizado($id);

        return $this->json(array_map(
            fn (array $fila) => [
                'id' => (int) $fila['id'],
                'fechaOrden' => (new \DateTimeImmutable($fila['fecha_orden']))->format(\DateTimeInterface::ATOM),
                'cliente' => $fila['cliente'],
                'producto' => $fila['producto'],
                'estado' => $fila['estado'],
            ],
            $this->ordenes->listarPorCas($cas),
        ));
    }

    #[Route('/{id}/ordenes/{ordenId}', name: 'orden_detalle', methods: ['GET'], requirements: ['id' => '\d+', 'ordenId' => '\d+'])]
    public function detalle(int $id, int $ordenId): JsonResponse
    {
        $cas = $this->casYUsuarioAutorizado($id);
        $orden = $this->buscarOrdenDeCas($cas, $ordenId);

        return $this->json($this->serializeOrden($orden));
    }

    /**
     * `diagnostico`/`reporteFinal`: los únicos campos editables después de creada la orden (ver
     * Version20261012192602 y OrdenService::actualizar()) — todo lo demás de la orden (producto,
     * cliente, fechas…) se fija al crearla.
     */
    #[Route('/{id}/ordenes/{ordenId}', name: 'orden_actualizar', methods: ['PATCH'], requirements: ['id' => '\d+', 'ordenId' => '\d+'])]
    public function actualizar(int $id, int $ordenId, Request $request): JsonResponse
    {
        $cas = $this->casYUsuarioAutorizado($id);
        $orden = $this->buscarOrdenDeCas($cas, $ordenId);

        $datos = json_decode($request->getContent(), true) ?? [];
        $orden = $this->ordenService->actualizar($orden, $datos);

        return $this->json($this->serializeOrden($orden));
    }

    /** PDF de la "solicitud de reparación" (ver OrdenPdfService) para ver/descargar bajo demanda — sin enviar ningún correo, a diferencia de enviarCorreo(). */
    #[Route('/{id}/ordenes/{ordenId}/pdf', name: 'orden_pdf', methods: ['GET'], requirements: ['id' => '\d+', 'ordenId' => '\d+'])]
    public function pdf(int $id, int $ordenId): Response
    {
        $cas = $this->casYUsuarioAutorizado($id);
        $orden = $this->buscarOrdenDeCas($cas, $ordenId);

        return new Response($this->ordenPdf->generar($orden), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('inline; filename="solicitud-reparacion-%d.pdf"', $orden->getId()),
        ]);
    }

    #[Route('/{id}/cas-clientes/buscar', name: 'orden_cas_cliente_buscar', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function buscarCliente(int $id, Request $request): JsonResponse
    {
        $cas = $this->casYUsuarioAutorizado($id);
        $cedula = (string) $request->query->get('cedula', '');
        $casCliente = $this->casClienteService->buscarPorCedula($cas, $cedula);

        return $this->json($casCliente !== null ? $this->serializeCliente($casCliente) : null);
    }

    #[Route('/{id}/ordenes', name: 'orden_crear', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function crear(int $id, Request $request): JsonResponse
    {
        $cas = $this->casYUsuarioAutorizado($id);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        $archivos = $request->files->all()['archivos'] ?? [];
        $orden = $this->ordenService->crear($cas, $usuario, $request->request->all(), $archivos);

        return $this->json($this->serializeOrden($orden), 201);
    }

    /**
     * Documentos adjuntos de una orden puntual, dado su cas y su propio id. Separado de
     * serializeOrden()/crear() porque el frontend necesita poder re-consultarlos después de
     * creada la orden (ej. para mostrarlos en una vista de detalle) sin traerse el resto de la
     * orden de nuevo.
     */
    #[Route('/{id}/ordenes/{ordenId}/adjuntos', name: 'orden_adjuntos_listar', methods: ['GET'], requirements: ['id' => '\d+', 'ordenId' => '\d+'])]
    public function listarAdjuntos(int $id, int $ordenId): JsonResponse
    {
        $cas = $this->casYUsuarioAutorizado($id);
        $orden = $this->buscarOrdenDeCas($cas, $ordenId);

        return $this->json($this->serializeAdjuntos($orden));
    }

    /**
     * Correo de "solicitud de reparación recibida" al cliente de la orden, con el PDF adjunto
     * (ver OrdenMailerService) — basado en .scratch/cas/email-orden/logica.php. Separado de
     * crear(): lo dispara el frontend DESPUÉS de confirmar que la orden quedó creada, así un
     * correo que falla (SMTP caído, etc.) nunca hace fallar ni revierte la creación de la orden
     * en sí. El frontend ya filtra por "hay correo y tiene formato válido" antes de llamar, pero
     * OrdenMailerService::enviar() igual valida (nunca confiar solo en el frontend).
     */
    #[Route('/{id}/ordenes/{ordenId}/enviar-correo', name: 'orden_enviar_correo', methods: ['POST'], requirements: ['id' => '\d+', 'ordenId' => '\d+'])]
    public function enviarCorreo(int $id, int $ordenId): JsonResponse
    {
        $cas = $this->casYUsuarioAutorizado($id);
        $orden = $this->buscarOrdenDeCas($cas, $ordenId);

        $envio =$this->ordenMailer->enviar($orden);

        return $this->json(['enviado' => $envio]);
    }

    private function casYUsuarioAutorizado(int $id): Cas
    {
        $cas = $this->casService->buscar($id);
        /** @var Usuario $usuario */
        $usuario = $this->getUser();

        if (!$this->casUsuarios->existe($cas, $usuario)) {
            throw new AccessDeniedHttpException('No tienes acceso a este centro de servicio.');
        }

        return $cas;
    }

    /** Filtra también por `cas` (no solo por id de orden): evita que, ya autorizado para SU cas, un usuario lea adjuntos de una orden ajena solo adivinando el id. */
    private function buscarOrdenDeCas(Cas $cas, int $ordenId): Orden
    {
        $orden = $this->ordenes->findOneBy(['id' => $ordenId, 'cas' => $cas]);
        if ($orden === null) {
            throw new NotFoundHttpException('Orden no encontrada en este centro de servicio.');
        }

        return $orden;
    }

    private function serializeCliente(CasCliente $casCliente): array
    {
        return [
            'id' => $casCliente->getId(),
            'cedula' => $casCliente->getNumeroIdentidad(),
            'nombre' => $casCliente->getNombre(),
            'apellido' => $casCliente->getApellido(),
            'telefono' => $casCliente->getTelefono(),
            'correo' => $casCliente->getCorreo(),
            'direccion' => $casCliente->getDireccion(),
        ];
    }

    private function serializeOrden(Orden $orden): array
    {
        $producto = $orden->getProducto();
        $casCliente = $orden->getCasCliente();

        return [
            'id' => $orden->getId(),
            'numero' => $orden->getNumero(),
            'comprobante' => $orden->getComprobante(),
            'estado' => $orden->getOrdenEstado()->getDescripcion(),
            'productoId' => $producto->getId(),
            'productoItem' => $producto->getItem(),
            'productoDescripcion' => $producto->getDescripcion(),
            // Misma convención de ProductoController::buscar() (ver ProductoImagenService) — para
            // mostrar la foto del producto en el detalle/edición de la orden, igual que en su
            // creación.
            'productoImagenUrl' => $this->productoImagen->url($producto->getItem(), $this->productoImagen->buscarAdjuntoTipo()),
            'cliente' => $this->serializeCliente($casCliente),
            'fechaFactura' => $orden->getFechaFactura()->format('Y-m-d'),
            'numeroFactura' => $orden->getNumeroFactura(),
            'descripcionCompra' => $orden->getDescripcionCompra(),
            'tipoIngreso' => $orden->getTipoIngreso(),
            'ordenRelaciona' => $orden->getOrdenRelaciona(),
            'reporteCliente' => $orden->getReporteCliente(),
            // diagnostico/reporteFinal se habilitan después de creada la orden (ver
            // Version20261012192602) — se exponen ya (siempre null al crear) para no tener que
            // volver a tocar este endpoint cuando se construya esa pantalla posterior.
            'diagnostico' => $orden->getDiagnostico(),
            'reporteFinal' => $orden->getReporteFinal(),
            'adjuntos' => $this->serializeAdjuntos($orden),
            'creadoEn' => $orden->getCreadoEn()->format(\DateTimeInterface::ATOM),
        ];
    }

    private function serializeAdjuntos(Orden $orden): array
    {
        return array_map(
            fn (OrdenAdjunto $a) => [
                'nombre' => $a->getNombre(),
                'url' => $this->urlAdjunto($a),
            ],
            $this->ordenAdjuntos->findBy(['orden' => $orden]),
        );
    }

    /**
     * Arma la URL de descarga del adjunto en tiempo de consulta: no se guarda ninguna ruta
     * completa — se reconstruye con adjuntoTipo.baseUrl + el mismo subdirectorio ofuscado de
     * cas/orden con el que ArchivoAdjuntoUploader guardó el archivo físico (ver
     * OrdenService::guardar() y ArchivoAdjuntoUploader::subdirectorioOrden(), fuente única de esa
     * ofuscación) + `ruta`, el nombre generado del archivo. No se usa `nombre`: ese es el nombre
     * original tal como se subió, deliberadamente distinto de `ruta` (ver Version20261009133021).
     */
    private function urlAdjunto(OrdenAdjunto $adjunto): ?string
    {
        $tipo = $adjunto->getAdjuntoTipo();
        if ($tipo === null || $tipo->getDirectorio() === null) {
            return null;
        }

        $orden = $adjunto->getOrden();
        $subdirectorio = $this->uploader->subdirectorioOrden($orden->getCas()->getId(), $tipo->getDirectorio(), $orden->getId());

        return $tipo->getBaseUrl().$subdirectorio.'/'.rawurlencode($adjunto->getRuta());
    }
}
