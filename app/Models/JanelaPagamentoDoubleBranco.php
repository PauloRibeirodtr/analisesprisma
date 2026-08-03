<?php

namespace App\Models;

use App\StatusJanelaPagamentoDoubleBranco;
use Database\Factories\JanelaPagamentoDoubleBrancoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JanelaPagamentoDoubleBranco extends Model
{
    /** @use HasFactory<JanelaPagamentoDoubleBrancoFactory> */
    use HasFactory;

    protected $table = 'janelas_pagamento_double_branco';

    protected $attributes = [
        'quantidade_velas_crash' => 1,
        'status' => StatusJanelaPagamentoDoubleBranco::Agendada->value,
        'tiros_na_janela' => 0,
        'tiros_acumulados_ciclo' => 0,
        'fonte_indisponivel' => false,
    ];

    protected $fillable = [
        'origem_id',
        'ciclo_id',
        'periodo_recuperacao_id',
        'inicio_em',
        'fim_em',
        'quantidade_velas_crash',
        'maior_multiplicador',
        'status',
        'ordem_no_ciclo',
        'tiros_na_janela',
        'tiros_acumulados_ciclo',
        'acerto_idblaze',
        'acerto_em',
        'fonte_indisponivel',
        'sincronizada_em',
        'apurada_em',
        'finalizada_em',
        'cancelada_em',
    ];

    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(CicloDoubleBranco::class, 'ciclo_id');
    }

    public function periodoRecuperacao(): BelongsTo
    {
        return $this->belongsTo(
            PeriodoRecuperacaoDoubleBranco::class,
            'periodo_recuperacao_id',
        );
    }

    public function scopePendentesDeApuracao(
        Builder $query,
        mixed $agora,
    ): Builder {
        return $query
            ->where('inicio_em', '<=', $agora)
            ->whereIn('status', [
                StatusJanelaPagamentoDoubleBranco::Agendada,
                StatusJanelaPagamentoDoubleBranco::Executando,
            ]);
    }

    public function scopeFinalizadas(Builder $query): Builder
    {
        return $query->whereIn('status', [
            StatusJanelaPagamentoDoubleBranco::Win,
            StatusJanelaPagamentoDoubleBranco::Loss,
        ]);
    }

    protected function casts(): array
    {
        return [
            'origem_id' => 'integer',
            'periodo_recuperacao_id' => 'integer',
            'inicio_em' => 'datetime',
            'fim_em' => 'datetime',
            'quantidade_velas_crash' => 'integer',
            'maior_multiplicador' => 'decimal:2',
            'status' => StatusJanelaPagamentoDoubleBranco::class,
            'ordem_no_ciclo' => 'integer',
            'tiros_na_janela' => 'integer',
            'tiros_acumulados_ciclo' => 'integer',
            'acerto_em' => 'datetime',
            'fonte_indisponivel' => 'boolean',
            'sincronizada_em' => 'datetime',
            'apurada_em' => 'datetime',
            'finalizada_em' => 'datetime',
            'cancelada_em' => 'datetime',
        ];
    }
}
