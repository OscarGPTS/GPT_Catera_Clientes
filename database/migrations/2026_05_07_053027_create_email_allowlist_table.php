<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_allowlist', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->json('allowed_providers')->nullable();
            $table->string('role_default')->nullable();
            $table->string('departamento_default')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_allowlist');
    }
};
