<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->index();
            $table->string('kpi');
            $table->decimal('valor', 18, 4);
            $table->json('contexto')->nullable();
            $table->timestamps();

            $table->index(['fecha', 'kpi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_snapshots');
    }
};
