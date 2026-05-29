<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->string('ci', 20)->primary();
            $table->string('nombre');
            $table->string('ap_paterno');
            $table->string('ap_materno')->nullable();
            $table->string('celular', 20)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('sexo', 1)->nullable(); // M o F
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
        
        // Add the foreign key for users table now that personas is created
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('persona_ci')->references('ci')->on('personas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['persona_ci']);
        });
        Schema::dropIfExists('personas');
    }
};
