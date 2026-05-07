<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas_bancarias', function (Blueprint $table) {
            $table->id();
            $table->enum('banco', ['bbva', 'banorte', 'banamex', 'santander', 'hsbc', 'otro']);
            $table->string('alias')->nullable();
            $table->string('numero_cuenta_enmascarado', 30);
            $table->string('clabe_enmascarada', 30)->nullable();
            $table->string('moneda', 3)->default('MXN');
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas_bancarias');
    }
};
