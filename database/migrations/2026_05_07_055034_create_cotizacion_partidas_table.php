<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizacion_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained('cotizaciones')->cascadeOnDelete();
            $table->unsignedSmallInteger('numero_partida');
            $table->text('descripcion');
            $table->decimal('cantidad', 12, 4)->default(1);
            $table->string('unidad', 20)->nullable();
            $table->decimal('costo_unitario', 15, 4)->default(0);
            $table->decimal('costo_total', 15, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['cotizacion_id', 'numero_partida']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizacion_partidas');
    }
};
