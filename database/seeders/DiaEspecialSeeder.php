<?php

namespace Database\Seeders;

use App\Models\DiaEspecial;
use Illuminate\Database\Seeder;

class DiaEspecialSeeder extends Seeder
{
    public function run(): void
    {
        $diasEspeciales = [
            [
                'nombre' => 'Año Nuevo',
                'fecha' => '2025-01-01',
                'tipo' => 'festivo',
                'impacto_demanda' => 'alto',
                'activo' => true,
            ],
            [
                'nombre' => 'Día de la Madre',
                'fecha' => '2025-05-11',
                'tipo' => 'festivo',
                'impacto_demanda' => 'alto',
                'activo' => true,
            ],
            [
                'nombre' => 'Día del Padre',
                'fecha' => '2025-06-15',
                'tipo' => 'festivo',
                'impacto_demanda' => 'medio',
                'activo' => true,
            ],
            [
                'nombre' => 'Fiestas Patrias',
                'fecha' => '2025-07-28',
                'tipo' => 'feriado',
                'impacto_demanda' => 'alto',
                'activo' => true,
            ],
            [
                'nombre' => 'Fiestas Patrias',
                'fecha' => '2025-07-29',
                'tipo' => 'feriado',
                'impacto_demanda' => 'alto',
                'activo' => true,
            ],
            [
                'nombre' => 'Navidad',
                'fecha' => '2025-12-25',
                'tipo' => 'festivo',
                'impacto_demanda' => 'alto',
                'activo' => true,
            ],
            [
                'nombre' => 'Año Nuevo',
                'fecha' => '2026-01-01',
                'tipo' => 'festivo',
                'impacto_demanda' => 'alto',
                'activo' => true,
            ],
            [
                'nombre' => 'Día de la Madre',
                'fecha' => '2026-05-10',
                'tipo' => 'festivo',
                'impacto_demanda' => 'alto',
                'activo' => true,
            ],
            [
                'nombre' => 'Día del Padre',
                'fecha' => '2026-06-21',
                'tipo' => 'festivo',
                'impacto_demanda' => 'medio',
                'activo' => true,
            ],
            [
                'nombre' => 'Fiestas Patrias',
                'fecha' => '2026-07-28',
                'tipo' => 'feriado',
                'impacto_demanda' => 'alto',
                'activo' => true,
            ],
            [
                'nombre' => 'Fiestas Patrias',
                'fecha' => '2026-07-29',
                'tipo' => 'feriado',
                'impacto_demanda' => 'alto',
                'activo' => true,
            ],
            [
                'nombre' => 'Navidad',
                'fecha' => '2026-12-25',
                'tipo' => 'festivo',
                'impacto_demanda' => 'alto',
                'activo' => true,
            ],
        ];

        foreach ($diasEspeciales as $dia) {
            DiaEspecial::updateOrCreate(
                [
                    'nombre' => $dia['nombre'],
                    'fecha' => $dia['fecha'],
                ],
                $dia
            );
        }
    }
}