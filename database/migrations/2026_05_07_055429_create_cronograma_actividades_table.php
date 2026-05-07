<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cronograma_actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cronograma_id')->constrained()->cascadeOnDelete();
            $table->string('codigo')->nullable();
            $table->string('nombre');
            $table->foreignId('parent_id')->nullable()->constrained('cronograma_actividades')->nullOnDelete();
            $table->date('fecha_inicio_planeada')->nullable();
            $table->date('fecha_fin_planeada')->nullable();
            $table->date('fecha_inicio_real')->nullable();
            $table->date('fecha_fin_real')->nullable();
            $table->decimal('porcentaje_avance', 5, 2)->default(0);
            $table->json('predecesoras')->nullable();
            $table->timestamps();

            $table->index(['cronograma_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cronograma_actividades');
    }
};
