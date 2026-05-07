<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_diaria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->text('relacion_actividades');
            $table->json('personal_gpt')->nullable();
            $table->json('equipos_en_sitio')->nullable();
            $table->json('proveedores_subcontratistas')->nullable();
            $table->string('vobo_cliente_nombre')->nullable();
            $table->string('vobo_cliente_organizacion')->nullable();
            $table->date('vobo_cliente_fecha')->nullable();
            $table->string('vobo_cliente_firma_path')->nullable();
            $table->foreignId('cargado_por_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('firmado_at')->nullable();
            $table->timestamps();

            $table->unique(['proyecto_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_diaria');
    }
};
