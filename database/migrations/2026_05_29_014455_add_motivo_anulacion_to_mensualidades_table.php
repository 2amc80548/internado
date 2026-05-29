<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mensualidades', function (Blueprint $table) {
            $table->string('motivo_anulacion')->nullable()->after('metodo_pago');
        });
    }

    public function down(): void
    {
        Schema::table('mensualidades', function (Blueprint $table) {
            $table->dropColumn('motivo_anulacion');
        });
    }
};
