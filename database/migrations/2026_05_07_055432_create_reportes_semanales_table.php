<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_semanales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->date('semana_inicio');
            $table->date('semana_fin');
            $table->longText('contenido_html')->nullable();
            $table->timestamp('generado_at')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->json('recipients')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->unique(['proyecto_id', 'semana_inicio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_semanales');
    }
};
