<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ReporteExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    ShouldAutoSize
{
    protected Collection $datos;

    protected string $tipo;

    public function __construct(
        Collection $datos,
        string $tipo
    ) {
        $this->datos = $datos;
        $this->tipo = $tipo;
    }

    /**
     * Datos que se escribirán en el Excel.
     */
    public function collection()
    {
        return $this->datos;
    }

    /**
     * Encabezados según el tipo de reporte.
     */
    public function headings(): array
    {
        return match ($this->tipo) {

            'productos' => [
                'Producto',
                'Categoría',
                'Precio',
                'Costo',
                'Stock',
                'Estado',
            ],

            'inventario' => [
                'ID',
                'Producto',
                'Categoría',
                'Precio',
                'Costo',
                'Stock actual',
                'Stock mínimo',
                'Stock de seguridad',
                'Estado',
                'Activo',
            ],

            'ventas' => [
                'Producto',
                'Cantidad vendida',
            ],

            'demanda' => [
                'Fecha',
                'Demanda',
            ],

            'predicciones' => [
                'Producto',
                'Stock actual',
                'Stock mínimo',
                'Stock de seguridad',
            ],

            'alertas' => [
                'Tipo',
                'Producto',
                'Descripción',
                'Estado',
            ],

            default => [],
        };
    }

    /**
     * Formato de cada fila.
     */
    public function map($dato): array
    {
        return match ($this->tipo) {

            'productos' => [
                $dato->nombre,
                $dato->categoria ?? 'Sin categoría',
                (float) $dato->precio,
                (float) $dato->costo,
                (int) $dato->stock,
                $dato->activo ? 'Activo' : 'Inactivo',
            ],

            'inventario' => [
                $dato->id,
                $dato->nombre,
                $dato->categoria ?? 'Sin categoría',
                (float) $dato->precio,
                (float) $dato->costo,
                (int) $dato->stock,
                (int) $dato->stock_minimo,
                (int) $dato->stock_seguridad,
                $dato->stock <= $dato->stock_minimo
                    ? 'Stock bajo'
                    : 'Disponible',
                $dato->activo ? 'Sí' : 'No',
            ],

            'ventas' => [
                $dato->nombre,
                (int) $dato->cantidad,
            ],

            'demanda' => [
                $dato->fecha,
                (int) $dato->cantidad,
            ],

            'predicciones' => [
                $dato->nombre,
                (int) $dato->stock,
                (int) $dato->stock_minimo,
                (int) $dato->stock_seguridad,
            ],

            'alertas' => [
                ucfirst($dato->tipo ?? 'informativa'),
                $dato->producto ?? 'Sin producto',
                $dato->descripcion ?? 'Sin descripción',
                ucfirst($dato->estado ?? 'pendiente'),
            ],

            default => [],
        };
    }
}