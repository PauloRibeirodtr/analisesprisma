<?php

namespace App\Actions\Usuarios;

use App\Actions\Assinaturas\ConcederTesteGratuito;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CriarUsuarioAdministrativamente
{
    public function __construct(
        private ConcederTesteGratuito $concederTesteGratuito,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     email: string,
     *     cpf_cnpj?: string|null,
     *     telefone?: string|null,
     *     perfil: string,
     *     password: string
     * }  $dados
     */
    public function executar(array $dados): User
    {
        return DB::transaction(function () use ($dados): User {
            $usuario = User::create([
                'name' => $dados['name'],
                'email' => $dados['email'],
                'cpf_cnpj' => $dados['cpf_cnpj'] ?? null,
                'telefone' => $dados['telefone'] ?? null,
                'perfil' => $dados['perfil'],
                'password' => Hash::make($dados['password']),
            ]);

            if ($usuario->perfil === 'cliente') {
                $this->concederTesteGratuito->executar($usuario);
            }

            return $usuario;
        });
    }
}
