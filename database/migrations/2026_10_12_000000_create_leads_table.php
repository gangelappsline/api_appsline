<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `App\Models\Lead` existía sin migración: Scramble no podía leer el esquema
     * (aviso MD001 "table `leads` does not exist") y POST /api/public/leads reventaba.
     * Columnas alineadas con la validación de Public\LeadController@store.
     */
    public function up(): void
    {
        // Si en tu base de datos la tabla ya se creó a mano, no se toca.
        if (Schema::hasTable('leads')) {
            return;
        }

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone', 20)->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
