<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        Rol::updateOrCreate(
            ['nombre' => 'Administrador'],
            [
                'descripcion' => 'Usuario responsable de administrar todo el sistema.',
                'activo' => true,
            ]
        );
    }
}