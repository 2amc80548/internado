<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('persona_ci', 20)->unique();
            $table->foreign('persona_ci')->references('ci')->on('personas')->onDelete('cascade');
            $table->foreignId('tutor_id')->nullable()->constrained('tutores')->onDelete('set null');
            $table->foreignId('comunidad_id')->nullable()->constrained('comunidades')->onDelete('set null');
            
            $table->string('colegio_origen')->nullable();
            $table->text('motivo_ingreso')->nullable();
            $table->text('enfermedad_base')->nullable();
            
            // Activo, Retirado, Bachiller, Graduado BTH
            $table->string('estado_global')->default('Activo'); 
            
            $table->integer('año_egreso_bachiller')->nullable();
            $table->integer('año_egreso_bth')->nullable();

            $table->string('ruta_foto')->nullable();
            $table->dateTime('edicion_habilitada_hasta')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
