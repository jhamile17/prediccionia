<?php

namespace App\Exports;

use App\Models\Producto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventarioExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Obtener productos para el reporte.
     */
    public function collection()
    {
        return Producto::with('categoria')
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Encabezados del Excel.
     */
    public function headings(): array
    {
        return [
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
        ];
    }

    /**
     * Formato de cada fila.
     */
    public function map($producto): array
    {
        $stock = (int) $producto->stock;
        $stockMinimo = (int) $producto->stock_minimo;

        if ($stock <= $stockMinimo) {
            $estado = 'Stock bajo';
        } else {
            $estado = 'Disponible';
        }

        return [
            $producto->id,
            $producto->nombre,
            $producto->categoria?->nombre ?? 'Sin categoría',
            $producto->precio,
            $producto->costo,
            $stock,
            $stockMinimo,
            $producto->stock_seguridad,
            $estado,
            $producto->activo ? 'Sí' : 'No',
        ];
    }
}