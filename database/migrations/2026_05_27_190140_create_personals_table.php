<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal', function (Blueprint $table) {
            $table->id();
            $table->string('persona_ci', 20)->unique();
            $table->foreign('persona_ci')->references('ci')->on('personas')->onDelete('cascade');
            $table->foreignId('tipo_personal_id')->constrained('tipos_personal')->onDelete('cascade');
            $table->string('profesion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal');
    }
};
