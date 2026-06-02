<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Modificar la columna 'email' en la tabla 'users' para que admita NULL
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        // 2. Agregar 'motivo_retiro' y 'motivo_anulacion' a la tabla 'estudiantes'
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->text('motivo_retiro')->nullable();
            $table->text('motivo_anulacion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir 'motivo_retiro' y 'motivo_anulacion'
        Schema::table('estudiantes', function (Blueprint $table) {
            $table->dropColumn(['motivo_retiro', 'motivo_anulacion']);
        });

        // Intentar revertir nullable de email
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
