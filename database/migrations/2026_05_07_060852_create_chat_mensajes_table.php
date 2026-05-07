<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('canal_id')->constrained('chat_canales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_message_id')->nullable()->constrained('chat_mensajes')->nullOnDelete();
            $table->text('contenido');
            $table->timestamp('edited_at')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();

            $table->index(['canal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_mensajes');
    }
};
