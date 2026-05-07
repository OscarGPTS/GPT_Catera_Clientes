<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_bancarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_cuenta_id')->constrained('estados_cuenta')->cascadeOnDelete();
            $table->date('fecha');
            $table->text('descripcion');
            $table->decimal('monto', 15, 2);
            $table->enum('tipo', ['ingreso', 'egreso']);
            $table->foreignId('conciliado_con_proyecto_id')->nullable()->constrained('proyectos')->nullOnDelete();
            $table->string('conciliado_con_factura')->nullable();
            $table->timestamp('conciliado_at')->nullable();
            $table->timestamps();

            $table->index(['estado_cuenta_id', 'fecha']);
            $table->index('conciliado_con_proyecto_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_bancarios');
    }
};
