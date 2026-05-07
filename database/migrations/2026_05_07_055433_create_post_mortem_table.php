<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_mortem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('fecha_sesion');
            $table->json('participantes')->nullable();
            $table->longText('lecciones_aprendidas')->nullable();
            $table->decimal('desviaciones_costo', 8, 4)->nullable();
            $table->decimal('desviaciones_tiempo', 8, 4)->nullable();
            $table->decimal('desviaciones_calidad', 8, 4)->nullable();
            $table->decimal('presupuesto_planeado', 15, 2)->nullable();
            $table->decimal('presupuesto_real', 15, 2)->nullable();
            $table->json('recomendaciones_mejora')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_mortem');
    }
};
