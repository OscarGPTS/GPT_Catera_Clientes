<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minuta_entrega_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('minuta_id')->constrained('minutas_entrega')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rol_en_minuta')->nullable();
            $table->boolean('firma_pendiente')->default(true);
            $table->timestamp('firmado_at')->nullable();
            $table->timestamps();

            $table->unique(['minuta_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minuta_entrega_participantes');
    }
};
