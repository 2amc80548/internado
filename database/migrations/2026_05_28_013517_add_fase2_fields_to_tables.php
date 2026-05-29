<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensualidades', function (Blueprint $table) {
            $table->string('metodo_pago')->nullable()->after('estado'); // QR, Efectivo
        });

        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->boolean('modo_mensualidad_automatica')->default(true)->after('color_hexadecimal');
            $table->boolean('edicion_perfil_habilitada')->default(false)->after('modo_mensualidad_automatica');
        });
    }

    public function down(): void
    {
        Schema::table('mensualidades', function (Blueprint $table) {
            $table->dropColumn('metodo_pago');
        });

        Schema::table('configuracion_sistema', function (Blueprint $table) {
            $table->dropColumn(['modo_mensualidad_automatica', 'edicion_perfil_habilitada']);
        });
    }
};
