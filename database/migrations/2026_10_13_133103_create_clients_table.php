<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            
            // Datos fiscales / empresa
            $table->string('business_name');              // Razón social
            $table->string('trade_name')->nullable();     // Nombre comercial
            $table->string('tax_id')->unique();           // NIT / RFC / RUT / CIF
            $table->string('tax_regime')->nullable();     // Régimen fiscal
            $table->string('industry')->nullable();       // Sector/industria
            
            // Contacto principal
            $table->string('contact_name');
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('secondary_phone', 30)->nullable();
            $table->string('website')->nullable();
            
            // Dirección
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('MX');
            $table->string('postal_code', 20)->nullable();
            
            // Datos comerciales
            $table->enum('status', ['active', 'inactive', 'prospect', 'suspended'])
                  ->default('prospect');
            $table->enum('client_type', ['individual', 'company'])
                  ->default('company');
            $table->decimal('credit_limit', 12, 2)->nullable();
            $table->unsignedInteger('payment_terms')->nullable(); // días de crédito
            
            // Notas / metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();         // campos extra flexibles
            
            // Auditoría
            $table->foreignId('account_manager_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();                       // Ejecutivo de cuenta
            
            $table->timestamp('onboarded_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            
            // Índices
            $table->index(['status', 'client_type']);
            $table->index('business_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};