<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('productos')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->enum('tipo', [
                'entrada',
                'salida',
                'ajuste',
                'reposicion'
            ]);

            $table->unsignedInteger('cantidad');

            $table->unsignedInteger('stock_anterior');
            $table->unsignedInteger('stock_nuevo');

            $table->string('motivo', 255)->nullable();

            $table->dateTime('fecha');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};