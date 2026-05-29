<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos_bth', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_tecnica_id')->constrained('carreras_tecnicas')->onDelete('cascade');
            $table->string('nivel');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos_bth');
    }
};
