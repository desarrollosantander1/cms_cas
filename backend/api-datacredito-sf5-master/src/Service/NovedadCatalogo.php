<?php

namespace App\Service;

use App\Domain\CalculadorNovedad;
use App\Domain\RangoDiasNovedad;
use App\Entity\Novedad;
use App\Repository\NovedadRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Puente entre el catálogo `novedad` (Doctrine) y el `CalculadorNovedad`
 * (dominio puro, sin Doctrine) — carga los rangos de días una sola vez por
 * request y arma el calculador con los umbrales vigentes de
 * datacredito.configuracion; también resuelve codigo -> Novedad para
 * quien necesite la entidad completa (ej. al armar un ReporteFactura).
 */
class NovedadCatalogo
{
    /** @var array<int, Novedad>|null codigo => Novedad */
    private ?array $porCodigo = null;

    public function __construct(
        private readonly NovedadRepository $novedades,
        private readonly ConfiguracionService $configuraciones,
    ) {
    }

    public function calculador(): CalculadorNovedad
    {
        $rangos = [];
        foreach ($this->todas() as $novedad) {
            if (!$novedad->isEsManual()) {
                $rangos[] = new RangoDiasNovedad($novedad->getCodigo(), $novedad->getRangoDias() ?? 0, $novedad->getRangoDiasHasta());
            }
        }

        return new CalculadorNovedad(
            $rangos,
            $this->configuraciones->novedadUmbralMaterialidad(),
            $this->configuraciones->novedadEdadMaximaDias(),
        );
    }

    public function porCodigo(int $codigo): Novedad
    {
        $novedad = $this->todas()[$codigo] ?? null;
        if ($novedad === null) {
            throw new NotFoundHttpException(sprintf('Código de novedad %d no existe en el catálogo.', $codigo));
        }

        return $novedad;
    }

    /** @return array<int, Novedad> codigo => Novedad */
    private function todas(): array
    {
        if ($this->porCodigo === null) {
            $this->porCodigo = [];
            foreach ($this->novedades->findAllOrdenado() as $novedad) {
                $this->porCodigo[$novedad->getCodigo()] = $novedad;
            }
        }

        return $this->porCodigo;
    }
}
