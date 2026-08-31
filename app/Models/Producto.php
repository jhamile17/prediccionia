<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'descripcion',
        'precio',
        'costo',
        'stock',
        'stock_minimo',
        'stock_seguridad',
        'activo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'costo' => 'decimal:2',
        'stock' => 'integer',
        'stock_minimo' => 'integer',
        'stock_seguridad' => 'integer',
        'activo' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    public function detallesVenta(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'producto_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'producto_id');
    }
    
}