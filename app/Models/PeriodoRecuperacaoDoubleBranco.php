<?php

namespace App\Models;

use App\StatusPeriodoRecuperacaoDoubleBranco;
use Database\Factories\PeriodoRecuperacaoDoubleBrancoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodoRecuperacaoDoubleBranco extends Model
{
    /** @use HasFactory<PeriodoRecuperacaoDoubleBrancoFactory> */
    use HasFactory;

    protected $table = 'periodos_recuperacao_double_branco';

    protected $attributes = [
        'status' => StatusPeriodoRecuperacaoDoubleBranco::Ativa->value,
    ];

    protected $fillable = [
        'status',
        'gatilho_resultado_id',
        'gatilho_idblaze',
        'iniciado_em',
        'primeiro_branco_resultado_id',
        'primeiro_branco_idblaze',
        'primeiro_branco_em',
        'segundo_branco_resultado_id',
        'segundo_branco_idblaze',
        'segundo_branco_em',
        'encerrado_em',
    ];

    public function janelas(): HasMany
    {
        return $this->hasMany(
            JanelaPagamentoDoubleBranco::class,
            'periodo_recuperacao_id',
        );
    }

    public function scopeAtivas(Builder $query): Builder
    {
        return $query->where(
            'status',
            StatusPeriodoRecuperacaoDoubleBranco::Ativa,
        );
    }

    protected function casts(): array
    {
        return [
            'status' => StatusPeriodoRecuperacaoDoubleBranco::class,
            'gatilho_resultado_id' => 'integer',
            'iniciado_em' => 'datetime',
            'primeiro_branco_resultado_id' => 'integer',
            'primeiro_branco_em' => 'datetime',
            'segundo_branco_resultado_id' => 'integer',
            'segundo_branco_em' => 'datetime',
            'encerrado_em' => 'datetime',
        ];
    }
}
