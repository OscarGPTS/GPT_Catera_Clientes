<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyecto_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tipo'); // cp_asignado, cp_aprobado, cotizacion_emitida, tech_reference_generado, dn_asignado, minuta_firmada, kom_interno, kom_cliente, viaticos_aprobados, bitacora_cargada, desviacion_reportada, carta_finiquito_emitida, post_mortem_completado
            $table->string('estado_anterior')->nullable();
            $table->string('estado_nuevo')->nullable();
            $table->json('payload')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();

            $table->index(['proyecto_id', 'tipo']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto_eventos');
    }
};
