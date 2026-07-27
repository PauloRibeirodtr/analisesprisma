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
        Schema::create('ciclos_double_branco', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('aberto')->index();
            $table->dateTime('iniciado_em', 3)->index();
            $table->dateTime('encerrado_em', 3)->nullable()->index();
            $table->unsignedInteger('total_janelas')->default(0);
            $table->unsignedInteger('total_tiros')->default(0);
            $table->unsignedInteger('total_losses')->default(0);
            $table->string('acerto_idblaze', 64)->nullable()->index();
            $table->dateTime('acerto_em', 3)->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciclos_double_branco');
    }
};
