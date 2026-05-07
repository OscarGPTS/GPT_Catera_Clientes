<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->string('avatar_url')->nullable()->after('email');
            $table->string('departamento')->nullable()->after('avatar_url');
            $table->string('puesto')->nullable()->after('departamento');
            $table->string('employee_id')->nullable()->after('puesto');
            $table->boolean('es_socio')->default(false)->after('employee_id');
            $table->boolean('es_socio_override')->nullable()->after('es_socio');
            $table->enum('status', ['active', 'invited', 'suspended'])->default('active')->after('es_socio_override');
            $table->timestamp('last_login_at')->nullable()->after('status');

            $table->index('departamento');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['departamento']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'avatar_url', 'departamento', 'puesto', 'employee_id',
                'es_socio', 'es_socio_override', 'status', 'last_login_at',
            ]);
        });
    }
};
