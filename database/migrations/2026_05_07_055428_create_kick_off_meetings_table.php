<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kick_off_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['kom_interno', 'kom_cliente']);
            $table->dateTime('fecha');
            $table->json('participantes')->nullable();
            $table->text('agenda')->nullable();
            $table->text('minuta')->nullable();
            $table->string('minuta_pdf_path')->nullable();
            $table->foreignId('cronograma_attached_id')->nullable();
            $table->timestamps();

            $table->index(['proyecto_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kick_off_meetings');
    }
};
