<?php

namespace App\Models;

use Database\Factories\PagamentoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pagamento extends Model
{
    /** @use HasFactory<PagamentoFactory> */
    use HasFactory;

    public const TIPOS = [
        'contratacao',
        'renovacao',
        'ajuste',
    ];

    public const FORMAS_PAGAMENTO = [
        'pix',
        'dinheiro',
        'transferencia',
        'cartao',
        'outro',
    ];

    protected $fillable = [
        'user_id',
        'assinatura_id',
        'valor',
        'data_pagamento',
        'tipo',
        'forma_pagamento',
        'comprovante',
        'registrado_por',
        'observacao',
        'gateway',
        'gateway_payment_id',
        'dados_gateway',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assinatura(): BelongsTo
    {
        return $this->belongsTo(Assinatura::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'data_pagamento' => 'datetime',
            'dados_gateway' => 'array',
        ];
    }
}
