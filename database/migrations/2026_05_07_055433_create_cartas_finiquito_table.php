<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartas_finiquito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('fecha_emision');
            $table->json('personal_liberado')->nullable();
            $table->json('equipos_liberados')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('firmado_cliente_at')->nullable();
            $table->timestamp('firmado_gpt_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartas_finiquito');
    }
};
