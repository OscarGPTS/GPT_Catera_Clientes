<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes_viaticos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->date('periodo_inicio');
            $table->date('periodo_fin');
            $table->text('justificacion')->nullable();
            $table->enum('status', ['borrador', 'pendiente_servgrales', 'pendiente_direccion', 'aprobada', 'rechazada'])->default('borrador');
            $table->foreignId('solicitante_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('aprobador_serv_grales_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprobador_direccion_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes_viaticos');
    }
};
