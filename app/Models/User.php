<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    public const PERFIS_ACESSO_IRRESTRITO = [
        'administrador',
        'analista',
    ];

    protected $attributes = [
        'perfil' => 'cliente',
    ];

    protected $fillable = [
        'name',
        'email',
        'cpf_cnpj',
        'telefone',
        'password',
        'perfil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function adminlte_desc(): string
    {
        return $this->email;
    }

    public function assinaturas(): HasMany
    {
        return $this->hasMany(Assinatura::class);
    }

    public function pagamentos(): HasMany
    {
        return $this->hasMany(Pagamento::class);
    }

    public function pagamentosRegistrados(): HasMany
    {
        return $this->hasMany(Pagamento::class, 'registrado_por');
    }

    public function possuiPlanoVigente(string $slug): bool
    {
        if (in_array($this->perfil, self::PERFIS_ACESSO_IRRESTRITO, true)) {
            return true;
        }

        return $this->assinaturas()
            ->vigentes()
            ->whereHas('plano', function ($query) use ($slug) {
                $query->whereIn('slug', [
                    $slug,
                    Plano::SLUG_TESTE,
                ]);
            })
            ->exists();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
