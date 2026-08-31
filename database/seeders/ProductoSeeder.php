<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    public function run(): void
    {
        $cafe = Categoria::where('nombre', 'Café')->first();
        $bebidasFrias = Categoria::where('nombre', 'Bebidas frías')->first();
        $alimentos = Categoria::where('nombre', 'Alimentos')->first();
        $postres = Categoria::where('nombre', 'Postres')->first();

        $productos = [
            [
                'categoria_id' => $cafe->id,
                'nombre' => 'Americano',
                'descripcion' => 'Café americano preparado con espresso y agua caliente.',
                'precio' => 7.00,
                'costo' => 2.50,
                'stock' => 40,
                'stock_minimo' => 10,
                'stock_seguridad' => 15,
                'activo' => true,
            ],
            [
                'categoria_id' => $cafe->id,
                'nombre' => 'Cappuccino',
                'descripcion' => 'Café espresso con leche vaporizada y espuma.',
                'precio' => 9.00,
                'costo' => 3.50,
                'stock' => 35,
                'stock_minimo' => 10,
                'stock_seguridad' => 15,
                'activo' => true,
            ],
            [
                'categoria_id' => $cafe->id,
                'nombre' => 'Latte',
                'descripcion' => 'Café espresso combinado con leche vaporizada.',
                'precio' => 9.50,
                'costo' => 3.80,
                'stock' => 30,
                'stock_minimo' => 8,
                'stock_seguridad' => 12,
                'activo' => true,
            ],
            [
                'categoria_id' => $cafe->id,
                'nombre' => 'Mocaccino',
                'descripcion' => 'Café espresso con leche y chocolate.',
                'precio' => 10.00,
                'costo' => 4.00,
                'stock' => 25,
                'stock_minimo' => 8,
                'stock_seguridad' => 12,
                'activo' => true,
            ],
            [
                'categoria_id' => $bebidasFrias->id,
                'nombre' => 'Cold Brew',
                'descripcion' => 'Café preparado mediante extracción en frío.',
                'precio' => 10.00,
                'costo' => 4.00,
                'stock' => 25,
                'stock_minimo' => 8,
                'stock_seguridad' => 12,
                'activo' => true,
            ],
            [
                'categoria_id' => $bebidasFrias->id,
                'nombre' => 'Frappé de Café',
                'descripcion' => 'Bebida fría de café preparada con hielo.',
                'precio' => 12.00,
                'costo' => 5.00,
                'stock' => 30,
                'stock_minimo' => 10,
                'stock_seguridad' => 15,
                'activo' => true,
            ],
            [
                'categoria_id' => $bebidasFrias->id,
                'nombre' => 'Frappé de Fresa',
                'descripcion' => 'Bebida fría de fresa con hielo.',
                'precio' => 12.00,
                'costo' => 5.00,
                'stock' => 30,
                'stock_minimo' => 10,
                'stock_seguridad' => 15,
                'activo' => true,
            ],
            [
                'categoria_id' => $bebidasFrias->id,
                'nombre' => 'Jugo de Maracuyá',
                'descripcion' => 'Bebida natural preparada con maracuyá.',
                'precio' => 8.00,
                'costo' => 3.00,
                'stock' => 35,
                'stock_minimo' => 10,
                'stock_seguridad' => 15,
                'activo' => true,
            ],
            [
                'categoria_id' => $alimentos->id,
                'nombre' => 'Pan con Jamón y Queso',
                'descripcion' => 'Pan acompañado de jamón y queso.',
                'precio' => 8.00,
                'costo' => 3.50,
                'stock' => 30,
                'stock_minimo' => 8,
                'stock_seguridad' => 12,
                'activo' => true,
            ],
            [
                'categoria_id' => $alimentos->id,
                'nombre' => 'Pan con Huevo',
                'descripcion' => 'Pan acompañado de huevo.',
                'precio' => 7.00,
                'costo' => 3.00,
                'stock' => 30,
                'stock_minimo' => 8,
                'stock_seguridad' => 12,
                'activo' => true,
            ],
            [
                'categoria_id' => $alimentos->id,
                'nombre' => 'Pan con Palta',
                'descripcion' => 'Pan acompañado de palta.',
                'precio' => 7.00,
                'costo' => 3.00,
                'stock' => 25,
                'stock_minimo' => 8,
                'stock_seguridad' => 12,
                'activo' => true,
            ],
            [
                'categoria_id' => $postres->id,
                'nombre' => 'Torta de Chocolate',
                'descripcion' => 'Porción de torta de chocolate.',
                'precio' => 10.00,
                'costo' => 4.50,
                'stock' => 20,
                'stock_minimo' => 6,
                'stock_seguridad' => 10,
                'activo' => true,
            ],
        ];

        foreach ($productos as $producto) {
            Producto::updateOrCreate(
                ['nombre' => $producto['nombre']],
                $producto
            );
        }
    }
}