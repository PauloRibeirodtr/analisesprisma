<?php

namespace App\Models;

use Database\Factories\AssinaturaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assinatura extends Model
{
    /** @use HasFactory<AssinaturaFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plano_id',
        'nome_plano',
        'valor_referencia',
        'duracao_dias',
        'inicio_em',
        'termino_em',
        'cancelada_em',
        'observacao',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class)->withTrashed();
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    public function estaVigente(): bool
    {
        return $this->cancelada_em === null
            && $this->inicio_em->lte(now())
            && $this->termino_em->gte(now());
    }

    public function estaVencida(): bool
    {
        return $this->termino_em->lt(now());
    }

    public function estaCancelada(): bool
    {
        return $this->cancelada_em !== null;
    }

    public function scopeVigentes(Builder $query): Builder
    {
        return $query
            ->whereNull('cancelada_em')
            ->where('inicio_em', '<=', now())
            ->where('termino_em', '>=', now());
    }

    public function scopeVencidas(Builder $query): Builder
    {
        return $query->where('termino_em', '<', now());
    }

    public function scopeFuturas(Builder $query): Builder
    {
        return $query->where('inicio_em', '>', now());
    }

    public function scopeCanceladas(Builder $query): Builder
    {
        return $query->whereNotNull('cancelada_em');
    }

    protected function casts(): array
    {
        return [
            'valor_referencia' => 'decimal:2',
            'duracao_dias' => 'integer',
            'inicio_em' => 'datetime',
            'termino_em' => 'datetime',
            'cancelada_em' => 'datetime',
        ];
    }
}
