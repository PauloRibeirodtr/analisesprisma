<?php

namespace Database\Seeders;

use App\Models\Plano;
use Illuminate\Database\Seeder;

class PlanoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plano = Plano::withTrashed()->firstOrNew([
            'slug' => Plano::SLUG_TESTE,
        ]);

        $plano->fill([
            'nome' => 'Teste gratuito',
            'descricao' => 'Acesso temporário gratuito a todos os módulos disponíveis para clientes.',
            'valor_padrao' => 0,
            'duracao_dias' => 1,
            'disponivel_para_venda' => false,
        ]);
        $plano->deleted_at = null;
        $plano->save();
    }
}
