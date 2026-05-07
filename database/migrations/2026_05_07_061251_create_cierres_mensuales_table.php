<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_mensuales', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('mes');
            $table->year('año');
            $table->enum('tipo', ['contable_sat', 'gerencial_avance']);
            $table->date('fecha_corte');
            $table->enum('status', ['borrador', 'aprobado', 'cerrado'])->default('borrador');
            $table->foreignId('generado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('aprobado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_at')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->unique(['mes', 'año', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_mensuales');
    }
};
