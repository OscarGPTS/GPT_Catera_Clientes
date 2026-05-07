<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_menciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensaje_id')->constrained('chat_mensajes')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('leido_at')->nullable();
            $table->timestamps();

            $table->unique(['mensaje_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_menciones');
    }
};
