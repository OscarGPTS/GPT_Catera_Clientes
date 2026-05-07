<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->decimal('costo_directo', 15, 2)->default(0);
            $table->decimal('factor_indirectos', 6, 4)->default(0);
            $table->decimal('factor_admin', 6, 4)->default(0);
            $table->decimal('factor_utilidad', 6, 4)->default(0);
            $table->decimal('precio_venta_calculado', 15, 2)->default(0);
            $table->decimal('precio_venta_final', 15, 2)->default(0);
            $table->decimal('margen_neto', 6, 4)->default(0);
            $table->string('moneda', 3)->default('USD');
            $table->enum('status', ['borrador', 'interno_aprobado', 'emitida', 'cancelada'])->default('borrador');
            $table->foreignId('generado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('fecha_emision')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->unique(['proyecto_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cotizaciones');
    }
};
