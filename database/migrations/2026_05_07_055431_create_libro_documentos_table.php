<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('libro_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seccion_id')->constrained('libro_secciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('archivo_path');
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamaño')->nullable();
            $table->foreignId('subido_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('subido_at')->useCurrent();
            $table->timestamps();

            $table->index('seccion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('libro_documentos');
    }
};
