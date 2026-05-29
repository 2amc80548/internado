<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidencias_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_internado_id')->constrained('registros_internado')->onDelete('cascade');
            $table->date('fecha');
            $table->string('tipo_falta'); // Leve, Grave, Muy Grave
            $table->text('descripcion');
            $table->text('sancion')->nullable();
            $table->string('estado_sancion')->default('Pendiente');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidencias_historial');
    }
};
