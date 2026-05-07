<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minutas_entrega', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('fecha_reunion');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->enum('modalidad', ['presencial', 'virtual', 'mixta'])->default('virtual');
            $table->json('orden_del_dia')->nullable();
            $table->json('acuerdos')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('firmado_at')->nullable();
            $table->enum('status', ['borrador', 'firmada'])->default('borrador');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minutas_entrega');
    }
};
