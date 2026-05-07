<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('tech_reference')->nullable()->unique();
            $table->string('cp_numero')->nullable()->unique();
            $table->string('dn_numero')->nullable()->unique();
            $table->year('año');
            $table->foreignId('cliente_id')->constrained()->restrictOnDelete();
            $table->foreignId('sublinea_id')->constrained()->restrictOnDelete();
            $table->string('usuario_final')->nullable();
            $table->string('sector')->nullable();
            $table->enum('estado', [
                'en_revision', 'cotizando', 'cotizado', 'presentado',
                'adjudicado_pendiente', 'adjudicado_firmado', 'en_ejecucion',
                'en_cierre', 'cerrado', 'cancelado', 'perdido', 'archivado',
            ])->default('en_revision');
            $table->text('resumen_ejecutivo')->nullable();
            $table->date('fecha_inicio_planeada')->nullable();
            $table->date('fecha_fin_planeada')->nullable();
            $table->enum('metodo_distribucion_plurianual', ['dias_naturales', 'hitos'])->default('dias_naturales');
            $table->decimal('monto_preliminar', 15, 2)->nullable();
            $table->string('moneda', 3)->default('USD');
            $table->foreignId('director_dn_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('gerente_proyectos_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('gerente_operaciones_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ingeniero_costos_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('ingeniero_proyectos_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('trainee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('estado');
            $table->index(['año', 'sublinea_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
