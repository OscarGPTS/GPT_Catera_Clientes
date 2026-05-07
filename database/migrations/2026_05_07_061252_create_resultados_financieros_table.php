<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_financieros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cierre_id')->constrained('cierres_mensuales')->cascadeOnDelete();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->decimal('ingresos_devengados', 15, 2)->default(0);
            $table->decimal('costos_devengados', 15, 2)->default(0);
            $table->decimal('utilidad_devengada', 15, 2)->default(0);
            $table->timestamp('snapshot_at')->useCurrent();
            $table->timestamps();

            $table->unique(['cierre_id', 'proyecto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_financieros');
    }
};
