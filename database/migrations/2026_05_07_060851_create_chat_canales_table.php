<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_canales', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['proyecto', 'departamento', 'direccion', 'privado']);
            $table->unsignedBigInteger('contexto_id')->nullable();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->foreignId('creado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tipo', 'contexto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_canales');
    }
};
