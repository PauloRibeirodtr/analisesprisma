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
        Schema::table('assinaturas', function (Blueprint $table) {
            $table->index(
                ['cancelada_em', 'termino_em'],
                'assinaturas_situacao_termino_index',
            );
            $table->index(
                ['plano_id', 'cancelada_em', 'inicio_em', 'termino_em'],
                'assinaturas_plano_vigencia_index',
            );
        });

        Schema::table('pagamentos', function (Blueprint $table) {
            $table->index(
                ['user_id', 'data_pagamento'],
                'pagamentos_cliente_data_index',
            );
            $table->index(
                ['data_pagamento', 'assinatura_id'],
                'pagamentos_data_assinatura_index',
            );
            $table->index(
                ['forma_pagamento', 'data_pagamento'],
                'pagamentos_forma_data_index',
            );
            $table->index(
                ['tipo', 'data_pagamento'],
                'pagamentos_tipo_data_index',
            );
            $table->index(
                ['registrado_por', 'data_pagamento'],
                'pagamentos_responsavel_data_index',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagamentos', function (Blueprint $table) {
            $table->dropIndex('pagamentos_cliente_data_index');
            $table->dropIndex('pagamentos_data_assinatura_index');
            $table->dropIndex('pagamentos_forma_data_index');
            $table->dropIndex('pagamentos_tipo_data_index');
            $table->dropIndex('pagamentos_responsavel_data_index');
        });

        Schema::table('assinaturas', function (Blueprint $table) {
            $table->dropIndex('assinaturas_situacao_termino_index');
            $table->dropIndex('assinaturas_plano_vigencia_index');
        });
    }
};
