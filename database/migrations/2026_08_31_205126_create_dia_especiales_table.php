<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dias_especiales', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 150);

            $table->date('fecha');

            $table->enum('tipo', [
                'feriado',
                'festivo',
                'evento',
                'campaña',
                'otro'
            ]);

            $table->enum('impacto_demanda', [
                'bajo',
                'medio',
                'alto'
            ])->default('medio');

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dias_especiales');
    }
};