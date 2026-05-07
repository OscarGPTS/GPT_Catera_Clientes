<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asignaciones_personas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('mes');
            $table->year('año');
            $table->unsignedSmallInteger('cp_asignados')->default(0);
            $table->unsignedSmallInteger('cp_ejecutados')->default(0);
            $table->unsignedSmallInteger('cp_remanentes')->default(0);
            $table->unsignedSmallInteger('cp_residual_anterior')->default(0);
            $table->unsignedSmallInteger('dn_activos')->default(0);
            $table->unsignedSmallInteger('dn_stand_by')->default(0);
            $table->unsignedSmallInteger('dn_cerrados')->default(0);
            $table->unsignedSmallInteger('dn_cancelados')->default(0);
            $table->unsignedSmallInteger('total_servicio')->default(0);
            $table->unsignedSmallInteger('total_suministro')->default(0);
            $table->enum('gerencia_regional', ['GRC', 'GRS', 'GRN', 'DG', 'GPT-IM'])->nullable();
            $table->timestamp('generado_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'mes', 'año']);
            $table->index(['año', 'mes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_personas');
    }
};
