<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libro_secciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('libro_id')->constrained('libros_proyecto')->cascadeOnDelete();
            $table->enum('codigo', ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J']);
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->decimal('porcentaje_avance', 5, 2)->default(0);
            $table->enum('estado', ['pendiente', 'en_proceso', 'completo'])->default('pendiente');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['libro_id', 'codigo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libro_secciones');
    }
};
