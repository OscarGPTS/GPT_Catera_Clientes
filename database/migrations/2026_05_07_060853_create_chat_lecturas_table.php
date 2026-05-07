<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_lecturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canal_id')->constrained('chat_canales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ultimo_mensaje_leido_id')->nullable()->constrained('chat_mensajes')->nullOnDelete();
            $table->timestamps();

            $table->unique(['canal_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_lecturas');
    }
};
