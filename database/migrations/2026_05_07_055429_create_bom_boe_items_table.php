<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bom_boe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->enum('tipo', ['BOM', 'BOE']);
            $table->text('descripcion');
            $table->decimal('cantidad', 12, 4)->default(1);
            $table->string('unidad', 20)->nullable();
            $table->enum('status', ['en_almacen', 'por_afilar', 'por_fabricar', 'por_comprar', 'en_transito', 'entregado'])->default('por_comprar');
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_requerida')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['proyecto_id', 'tipo']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_boe_items');
    }
};
