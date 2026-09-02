<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * Mostrar listado de categorías.
     */
    public function index(Request $request)
    {
        $query = Categoria::withCount('productos');

        // Buscar por nombre o descripción
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%");
            });
        }

        // Filtrar por estado
        if ($request->filled('estado')) {

            if ($request->estado === 'activo') {
                $query->where('activo', true);
            }

            if ($request->estado === 'inactivo') {
                $query->where('activo', false);
            }
        }

        $categorias = $query
            ->orderBy('nombre')
            ->get();

        $totalCategorias = Categoria::count();

        $categoriasActivas = Categoria::where('activo', true)->count();

        $categoriasInactivas = Categoria::where('activo', false)->count();

        $productosAsociados = Categoria::withCount('productos')
            ->get()
            ->sum('productos_count');

        return view('categorias.index', compact(
            'categorias',
            'totalCategorias',
            'categoriasActivas',
            'categoriasInactivas',
            'productosAsociados'
        ));
    }

    /**
     * Mostrar formulario para crear categoría.
     */
    public function create()
    {
        return view('categorias.create');
    }

    /**
     * Registrar nueva categoría.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:categorias,nombre',
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',

            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',

            'nombre.unique' => 'Ya existe una categoría con ese nombre.',

            'descripcion.max' => 'La descripción no puede superar los 500 caracteres.',
        ]);

        $validated['activo'] = true;

        Categoria::create($validated);

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría registrada correctamente.');
    }

    /**
     * Mostrar formulario para editar categoría.
     */
    public function edit(Categoria $categoria)
    {
        return view('categorias.edit', compact('categoria'));
    }

    /**
     * Actualizar categoría.
     */
    public function update(Request $request, Categoria $categoria)
    {
        $validated = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:categorias,nombre,' . $categoria->id,
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ], [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',

            'nombre.max' => 'El nombre no puede superar los 100 caracteres.',

            'nombre.unique' => 'Ya existe otra categoría con ese nombre.',

            'descripcion.max' => 'La descripción no puede superar los 500 caracteres.',
        ]);

        $categoria->update($validated);

        return redirect()
            ->route('categorias.index')
            ->with('success', 'Categoría actualizada correctamente.');
    }

    /**
     * Activar / desactivar categoría.
     */
    public function toggle(Categoria $categoria)
    {
        $categoria->update([
            'activo' => !$categoria->activo,
        ]);

        $mensaje = $categoria->activo
            ? 'Categoría activada correctamente.'
            : 'Categoría desactivada correctamente.';

        return redirect()
            ->route('categorias.index')
            ->with('success', $mensaje);
    }
}