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
        Schema::table('janelas_pagamento_double_branco', function (Blueprint $table) {
            $table->foreignId('periodo_recuperacao_id')
                ->nullable()
                ->after('ciclo_id')
                ->constrained('periodos_recuperacao_double_branco')
                ->restrictOnDelete();
            $table->dateTime('cancelada_em', 3)
                ->nullable()
                ->after('finalizada_em')
                ->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('janelas_pagamento_double_branco', function (Blueprint $table) {
            $table->dropConstrainedForeignId('periodo_recuperacao_id');
            $table->dropColumn('cancelada_em');
        });
    }
};
