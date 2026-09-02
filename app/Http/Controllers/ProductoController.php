<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Mostrar productos.
     */
    public function index(Request $request)
    {
        $query = Producto::with('categoria');

        // Buscar por nombre
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        // Filtrar por categoría
        if ($request->filled('categoria')) {
            $query->where('categoria_id', $request->categoria);
        }

        $productos = $query
            ->orderBy('nombre')
            ->get();

        $categorias = Categoria::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('productos.index', compact(
            'productos',
            'categorias'
        ));
    }

    /**
     * Mostrar formulario para registrar producto.
     */
    public function create()
    {
        $categorias = Categoria::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('productos.create', compact('categorias'));
    }

    /**
     * Guardar nuevo producto.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:500',
            'precio' => 'required|numeric|min:0',
            'costo' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'stock_seguridad' => 'required|integer|min:0',
            'activo' => 'nullable|boolean',
        ], [
            'categoria_id.required' => 'Debes seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.numeric' => 'El precio debe ser numérico.',
            'precio.min' => 'El precio no puede ser negativo.',
            'costo.required' => 'El costo es obligatorio.',
            'costo.numeric' => 'El costo debe ser numérico.',
            'costo.min' => 'El costo no puede ser negativo.',
            'stock.required' => 'El stock es obligatorio.',
            'stock.integer' => 'El stock debe ser un número entero.',
            'stock.min' => 'El stock no puede ser negativo.',
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.integer' => 'El stock mínimo debe ser un número entero.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
            'stock_seguridad.required' => 'El stock de seguridad es obligatorio.',
            'stock_seguridad.integer' => 'El stock de seguridad debe ser un número entero.',
            'stock_seguridad.min' => 'El stock de seguridad no puede ser negativo.',
        ]);

        $validated['activo'] = $request->has('activo');

        Producto::create($validated);

        return redirect()
            ->route('productos.index')
            ->with('success', 'Producto registrado correctamente.');
    }
}