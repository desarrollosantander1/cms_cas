<?php

namespace App\Service\GeneradorCartas;

use App\Domain\CalculadorInteres;
use App\Entity\TasaInteres;
use App\Repository\Gbl\EmpleadoRepository;
use App\Repository\TasaInteresRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Tasa de mora vigente del Generador de Cartas. Lee/escribe
 * datacredito.tasa_interes; si la tabla está vacía usa
 * CalculadorInteres::TASA_MENSUAL_DEFECTO (2%). Cada actualización es una
 * fila nueva — histórico, sin borrado ni update in-place.
 */
final class TasaInteresService
{
    public function __construct(
        private readonly TasaInteresRepository $tasas,
        private readonly EmpleadoRepository $empleados,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /** Valor numérico de la tasa vigente, para el cálculo del interés. */
    public function tasaVigente(): float
    {
        $vigente = $this->tasas->vigente();

        return $vigente !== null ? (float) $vigente->getTasaMensual() : CalculadorInteres::TASA_MENSUAL_DEFECTO;
    }

    /** @return array<string, mixed> {tasaMensual, actualizadoEn, actualizadoPor, porDefecto} */
    public function estadoVigente(): array
    {
        $vigente = $this->tasas->vigente();

        if ($vigente === null) {
            return [
                'tasaMensual' => CalculadorInteres::TASA_MENSUAL_DEFECTO,
                'actualizadoEn' => null,
                'actualizadoPor' => null,
                'porDefecto' => true,
            ];
        }

        return [
            'tasaMensual' => (float) $vigente->getTasaMensual(),
            'actualizadoEn' => $vigente->getCreadoEn()->format(\DateTimeInterface::ATOM),
            'actualizadoPor' => $this->nombreEmpleado($vigente->getEmpleadoId()),
            'porDefecto' => false,
        ];
    }

    /** @return array<string, mixed> el nuevo estado vigente (mismo shape que estadoVigente) */
    public function actualizar(float $tasaMensual, int $empleadoId): array
    {
        if (!is_finite($tasaMensual) || $tasaMensual <= 0) {
            throw new BadRequestHttpException('La tasa mensual debe ser un número mayor que 0.');
        }

        $registro = new TasaInteres(number_format($tasaMensual, 3, '.', ''), $empleadoId);
        $this->em->persist($registro);
        $this->em->flush();

        return $this->estadoVigente();
    }

    private function nombreEmpleado(int $empleadoId): ?string
    {
        $empleado = $this->empleados->find($empleadoId);
        if ($empleado === null) {
            return null;
        }

        $usuario = $empleado->getUsuario();
        $nombre = trim(sprintf('%s %s', $usuario->getNombre() ?? '', $usuario->getApellido() ?? ''));

        return $nombre !== '' ? $nombre : $usuario->getEmail();
    }
}
