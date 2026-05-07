<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libros_proyecto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('fecha_apertura');
            $table->date('fecha_cierre_estimado')->nullable();
            $table->date('fecha_cierre_real')->nullable();
            $table->decimal('porcentaje_avance_global', 5, 2)->default(0);
            $table->boolean('bloqueado_para_cierre')->default(true);
            $table->string('pdf_consolidado_path')->nullable();
            $table->string('pdf_consolidado_md5', 32)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libros_proyecto');
    }
};
