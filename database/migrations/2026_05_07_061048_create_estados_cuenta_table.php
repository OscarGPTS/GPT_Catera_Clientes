<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_cuenta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_id')->constrained('cuentas_bancarias')->cascadeOnDelete();
            $table->unsignedTinyInteger('mes');
            $table->year('año');
            $table->string('archivo_origen_path')->nullable();
            $table->timestamp('parseado_at')->nullable();
            $table->unsignedInteger('total_movimientos')->default(0);
            $table->timestamps();

            $table->unique(['cuenta_id', 'mes', 'año']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_cuenta');
    }
};
