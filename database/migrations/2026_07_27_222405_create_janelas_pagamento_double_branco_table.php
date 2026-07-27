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
        Schema::create('janelas_pagamento_double_branco', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('origem_id')->unique();
            $table->foreignId('ciclo_id')
                ->nullable()
                ->constrained('ciclos_double_branco')
                ->restrictOnDelete();
            $table->dateTime('inicio_em', 3);
            $table->dateTime('fim_em', 3);
            $table->unsignedInteger('quantidade_velas_crash')->default(1);
            $table->decimal('maior_multiplicador', 20, 2);
            $table->string('status', 20)->default('agendada');
            $table->unsignedInteger('ordem_no_ciclo')->nullable();
            $table->unsignedInteger('tiros_na_janela')->default(0);
            $table->unsignedInteger('tiros_acumulados_ciclo')->default(0);
            $table->string('acerto_idblaze', 64)->nullable();
            $table->dateTime('acerto_em', 3)->nullable();
            $table->boolean('fonte_indisponivel')->default(false);
            $table->dateTime('sincronizada_em', 3)->nullable();
            $table->dateTime('apurada_em', 3)->nullable();
            $table->dateTime('finalizada_em', 3)->nullable();
            $table->timestamps();

            $table->index(
                ['status', 'inicio_em'],
                'janelas_double_status_inicio_index',
            );
            $table->index(
                ['status', 'fim_em'],
                'janelas_double_status_fim_index',
            );
            $table->index(
                ['ciclo_id', 'ordem_no_ciclo'],
                'janelas_double_ciclo_ordem_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('janelas_pagamento_double_branco');
    }
};
