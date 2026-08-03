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
        Schema::create('periodos_recuperacao_double_branco', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('ativa')->index();
            $table->unsignedBigInteger('gatilho_resultado_id');
            $table->string('gatilho_idblaze', 64);
            $table->dateTime('iniciado_em', 3)->index();
            $table->unsignedBigInteger('primeiro_branco_resultado_id')->nullable();
            $table->string('primeiro_branco_idblaze', 64)->nullable();
            $table->dateTime('primeiro_branco_em', 3)->nullable();
            $table->unsignedBigInteger('segundo_branco_resultado_id')->nullable();
            $table->string('segundo_branco_idblaze', 64)->nullable();
            $table->dateTime('segundo_branco_em', 3)->nullable();
            $table->dateTime('encerrado_em', 3)->nullable()->index();
            $table->timestamps();

            $table->index(
                ['status', 'iniciado_em'],
                'recuperacao_double_status_inicio_index',
            );
            $table->unique(
                'gatilho_idblaze',
                'recuperacao_double_gatilho_unique',
            );
            $table->unique(
                'primeiro_branco_idblaze',
                'recuperacao_double_primeiro_branco_unique',
            );
            $table->unique(
                'segundo_branco_idblaze',
                'recuperacao_double_segundo_branco_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos_recuperacao_double_branco');
    }
};
