<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_lineas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('cierres_secciones')->cascadeOnDelete();
            $table->foreignId('proyecto_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('monto', 15, 2);
            $table->decimal('porcentaje_aplicado', 5, 4)->default(1.0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('seccion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_lineas');
    }
};
