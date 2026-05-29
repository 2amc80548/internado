<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensualidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_internado_id')->constrained('registros_internado')->onDelete('cascade');
            $table->string('mes'); // Enero, Febrero, etc. o un enum
            $table->decimal('monto', 8, 2);
            $table->date('fecha_pago')->nullable();
            $table->decimal('total', 8, 2);
            $table->string('estado')->default('Pendiente'); // Pendiente, Pendiente_Verificacion, Pagado
            $table->string('ruta_comprobante_qr')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensualidades');
    }
};
