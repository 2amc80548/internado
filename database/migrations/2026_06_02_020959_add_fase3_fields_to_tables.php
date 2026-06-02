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
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->string('nombre_sistema')->default('INTERNADO');
            $table->string('whatsapp_notificaciones')->nullable();
            $table->string('ruta_logo_login')->nullable();
            $table->json('permisos_encargada')->nullable();
        });

        Schema::table('registros_internado', function (Blueprint $table) {
            $table->text('motivo_retiro')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->dropColumn(['nombre_sistema', 'whatsapp_notificaciones', 'ruta_logo_login', 'permisos_encargada']);
        });

        Schema::table('registros_internado', function (Blueprint $table) {
            $table->dropColumn('motivo_retiro');
        });
    }
};
