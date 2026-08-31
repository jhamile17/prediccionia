<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            UserSeeder::class,
            CategoriaSeeder::class,
            ProductoSeeder::class,
            DiaEspecialSeeder::class,
            VentaSeeder::class,
            MovimientoInventarioSeeder::class,
        ]);
    }
}