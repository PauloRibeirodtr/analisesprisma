<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pagamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->index()
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('assinatura_id')
                ->index()
                ->constrained('assinaturas')
                ->restrictOnDelete();
            $table->decimal('valor', 10, 2);
            $table->dateTime('data_pagamento')->index();
            $table->enum('tipo', [
                'contratacao',
                'renovacao',
                'ajuste',
            ])->index();
            $table->enum('forma_pagamento', [
                'pix',
                'dinheiro',
                'transferencia',
                'cartao',
                'outro',
            ])->nullable();
            $table->string('comprovante')->nullable();
            $table->foreignId('registrado_por')
                ->index()
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('observacao')->nullable();
            $table->string('gateway')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->json('dados_gateway')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagamentos');
    }
};
