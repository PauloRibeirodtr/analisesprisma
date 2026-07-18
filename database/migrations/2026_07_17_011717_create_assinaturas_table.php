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
        Schema::create('assinaturas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->index()
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('plano_id')
                ->index()
                ->constrained('planos')
                ->restrictOnDelete();
            $table->string('nome_plano');
            $table->decimal('valor_referencia', 10, 2);
            $table->unsignedInteger('duracao_dias');
            $table->dateTime('inicio_em')->index();
            $table->dateTime('termino_em')->index();
            $table->dateTime('cancelada_em')->nullable()->index();
            $table->text('observacao')->nullable();
            $table->timestamps();

            $table->index(
                ['user_id', 'cancelada_em', 'inicio_em', 'termino_em'],
                'assinaturas_vigencia_usuario_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assinaturas');
    }
};
