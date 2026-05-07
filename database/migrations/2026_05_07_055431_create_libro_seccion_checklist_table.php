<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libro_seccion_checklist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('libro_secciones')->cascadeOnDelete();
            $table->string('item_descripcion');
            $table->boolean('completado')->default(false);
            $table->foreignId('evidencia_documento_id')->nullable();
            $table->foreignId('completado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completado_at')->nullable();
            $table->timestamps();

            $table->index('seccion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libro_seccion_checklist');
    }
};
