<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_internas', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['requisicion_compras', 'orden_trabajo_ingenieria']);
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->string('cp_numero')->nullable();
            $table->string('codigo_formato')->nullable();
            $table->enum('estado', ['borrador', 'emitida', 'en_proceso', 'respondida', 'cancelada'])->default('borrador');
            $table->foreignId('solicitante_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('asignado_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_solicitud')->nullable();
            $table->date('fecha_respuesta_requerida')->nullable();
            $table->date('fecha_respuesta_real')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('respuesta')->nullable();
            $table->timestamps();

            $table->index(['proyecto_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_internas');
    }
};
