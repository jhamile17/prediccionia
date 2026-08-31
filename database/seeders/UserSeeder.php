<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdministrador = Rol::where('nombre', 'Administrador')->firstOrFail();

        User::updateOrCreate(
            [
                'email' => 'admin@sistemaprediccion.com',
            ],
            [
                'name' => 'Administrador',
                'email' => 'admin@sistemaprediccion.com',
                'password' => Hash::make('Admin12345'),
                'rol_id' => $rolAdministrador->id,
            ]
        );
    }
}