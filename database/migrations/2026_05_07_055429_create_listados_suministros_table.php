<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listados_suministros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('porcentaje_avance_global', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listados_suministros');
    }
};
