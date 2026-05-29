<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registros_internado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->onDelete('cascade');
            $table->foreignId('gestion_id')->constrained('gestiones')->onDelete('cascade');
            $table->foreignId('curso_id')->nullable()->constrained('cursos')->onDelete('set null');
            $table->foreignId('curso_bth_id')->nullable()->constrained('cursos_bth')->onDelete('set null');
            
            $table->string('pabellon')->nullable();
            $table->string('cama')->nullable();
            $table->text('observacion')->nullable();
            // Cursando, Aprobado, Reprobado, Retirado
            $table->string('estado_anual')->default('Cursando');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registros_internado');
    }
};
