<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Café',
                'descripcion' => 'Productos preparados principalmente a base de café.',
                'activo' => true,
            ],
            [
                'nombre' => 'Bebidas frías',
                'descripcion' => 'Bebidas frías y refrescantes.',
                'activo' => true,
            ],
            [
                'nombre' => 'Alimentos',
                'descripcion' => 'Alimentos y productos para acompañar las bebidas.',
                'activo' => true,
            ],
            [
                'nombre' => 'Postres',
                'descripcion' => 'Postres y productos dulces.',
                'activo' => true,
            ],
        ];

        foreach ($categorias as $categoria) {
            Categoria::updateOrCreate(
                ['nombre' => $categoria['nombre']],
                $categoria
            );
        }
    }
}