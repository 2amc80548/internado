<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boletines_calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_internado_id')->constrained('registros_internado')->onDelete('cascade');
            $table->integer('numero_periodo'); // 1, 2, 3
            $table->string('ruta_archivo');
            $table->string('estado_aprobacion')->default('Pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletines_calificaciones');
    }
};
