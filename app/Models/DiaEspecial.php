<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiaEspecial extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'fecha',
        'tipo',
        'impacto_demanda',
        'activo',
    ];

    protected $casts = [
        'fecha' => 'date',
        'activo' => 'boolean',
    ];

    public function getTable()
    {
        return 'dias_especiales';
    }
}