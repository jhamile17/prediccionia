<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('categoria_id')
                ->constrained('categorias')
                ->restrictOnDelete();

            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();

            $table->decimal('precio', 10, 2);
            $table->decimal('costo', 10, 2);

            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('stock_minimo')->default(5);
            $table->unsignedInteger('stock_seguridad')->default(10);

            $table->boolean('activo')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};