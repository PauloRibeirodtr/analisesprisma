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
            $table->string('status', 30)
                ->default('agendada')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('janelas_pagamento_double_branco', function (Blueprint $table) {
            $table->string('status', 20)
                ->default('agendada')
                ->change();
        });
    }
};
