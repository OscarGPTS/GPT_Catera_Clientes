<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secuencias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20); // 'cp' | 'dn'
            $table->year('año');
            $table->integer('ultimo_consecutivo')->default(0);
            $table->timestamps();

            $table->unique(['tipo', 'año']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencias');
    }
};
