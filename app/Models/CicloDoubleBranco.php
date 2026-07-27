<?php

namespace App\Models;

use App\StatusCicloDoubleBranco;
use Database\Factories\CicloDoubleBrancoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CicloDoubleBranco extends Model
{
    /** @use HasFactory<CicloDoubleBrancoFactory> */
    use HasFactory;

    protected $table = 'ciclos_double_branco';

    protected $attributes = [
        'status' => StatusCicloDoubleBranco::Aberto->value,
        'total_janelas' => 0,
        'total_tiros' => 0,
        'total_losses' => 0,
    ];

    protected $fillable = [
        'status',
        'iniciado_em',
        'encerrado_em',
        'total_janelas',
        'total_tiros',
        'total_losses',
        'acerto_idblaze',
        'acerto_em',
    ];

    public function janelas(): HasMany
    {
        return $this->hasMany(
            JanelaPagamentoDoubleBranco::class,
            'ciclo_id',
        );
    }

    public function scopeAbertos(Builder $query): Builder
    {
        return $query->where('status', StatusCicloDoubleBranco::Aberto);
    }

    protected function casts(): array
    {
        return [
            'status' => StatusCicloDoubleBranco::class,
            'iniciado_em' => 'datetime',
            'encerrado_em' => 'datetime',
            'total_janelas' => 'integer',
            'total_tiros' => 'integer',
            'total_losses' => 'integer',
            'acerto_em' => 'datetime',
        ];
    }
}
