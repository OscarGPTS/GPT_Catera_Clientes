<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viaticos_partidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('solicitudes_viaticos')->cascadeOnDelete();
            $table->enum('concepto', ['hospedaje', 'alimentos', 'transporte', 'otros']);
            $table->decimal('monto_estimado', 12, 2)->default(0);
            $table->decimal('monto_real', 12, 2)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viaticos_partidas');
    }
};
