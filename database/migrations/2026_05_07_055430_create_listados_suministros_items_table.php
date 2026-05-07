<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listados_suministros_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listado_id')->constrained('listados_suministros')->cascadeOnDelete();
            $table->text('descripcion');
            $table->decimal('cantidad', 12, 4)->default(1);
            $table->string('unidad', 20)->nullable();
            $table->date('fecha_requerida')->nullable();
            $table->string('status')->default('definicion');
            $table->decimal('porcentaje_avance', 5, 2)->default(0);
            $table->string('etapa')->nullable(); // 0-25, 26-50, 51-75, 76-100
            $table->timestamps();

            $table->index('listado_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listados_suministros_items');
    }
};
