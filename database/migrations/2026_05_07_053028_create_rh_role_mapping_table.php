<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rh_role_mapping', function (Blueprint $table) {
            $table->id();
            $table->string('puesto_rh');
            $table->string('rol_sistema');
            $table->integer('prioridad')->default(50);
            $table->string('departamento_filter')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'prioridad']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rh_role_mapping');
    }
};
