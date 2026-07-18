<?php

namespace App\Http\Requests\Admin;

use App\Models\Pagamento;
use App\Models\Plano;
use App\Models\User;
use App\Queries\Admin\RelatorioAdministrativo;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRelatorioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('administrar-clientes') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'relatorio' => [
                'nullable',
                Rule::in(array_keys(RelatorioAdministrativo::RELATORIOS)),
            ],
            'cliente_id' => [
                'nullable',
                'integer',
                Rule::exists((new User)->getTable(), 'id')
                    ->where('perfil', 'cliente'),
            ],
            'plano_id' => [
                'nullable',
                'integer',
                Rule::exists((new Plano)->getTable(), 'id'),
            ],
            'inicio' => ['nullable', 'date_format:Y-m-d'],
            'fim' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:inicio',
            ],
            'forma_pagamento' => [
                'nullable',
                Rule::in(Pagamento::FORMAS_PAGAMENTO),
            ],
            'tipo' => [
                'nullable',
                Rule::in(Pagamento::TIPOS),
            ],
            'registrado_por' => [
                'nullable',
                'integer',
                Rule::exists((new User)->getTable(), 'id')
                    ->whereIn('perfil', User::PERFIS_ACESSO_IRRESTRITO),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'fim.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
            'relatorio.in' => 'Selecione um relatório válido.',
        ];
    }
}
