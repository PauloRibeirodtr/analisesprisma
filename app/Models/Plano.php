<?php

namespace App\Models;

use Database\Factories\PlanoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plano extends Model
{
    /** @use HasFactory<PlanoFactory> */
    use HasFactory;

    use SoftDeletes;

    public const SLUG_TESTE = 'teste';

    protected $attributes = [
        'duracao_dias' => 30,
        'disponivel_para_venda' => true,
    ];

    protected $fillable = [
        'nome',
        'slug',
        'descricao',
        'valor_padrao',
        'duracao_dias',
        'disponivel_para_venda',
    ];

    public function assinaturas(): HasMany
    {
        return $this->hasMany(Assinatura::class);
    }

    public function ePlanoSistema(): bool
    {
        return $this->slug === self::SLUG_TESTE;
    }

    protected function casts(): array
    {
        return [
            'valor_padrao' => 'decimal:2',
            'duracao_dias' => 'integer',
            'disponivel_para_venda' => 'boolean',
        ];
    }
}
