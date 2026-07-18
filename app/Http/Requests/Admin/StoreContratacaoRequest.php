<?php

namespace App\Http\Requests\Admin;

use App\Models\Pagamento;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContratacaoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $cliente = $this->route('cliente');

        return $cliente instanceof User
            && $cliente->perfil === 'cliente'
            && $this->user()?->can('administrar-clientes') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plano_id' => [
                'required',
                'integer',
                Rule::exists('planos', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->whereNull('deleted_at')
                        ->where('disponivel_para_venda', true),
                ),
            ],
            'inicio_em' => ['required', 'date'],
            'duracao_dias' => ['required', 'integer', 'min:1', 'max:3650'],
            'valor_pago' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'data_pagamento' => ['required', 'date'],
            'forma_pagamento' => [
                'required',
                'string',
                Rule::in(Pagamento::FORMAS_PAGAMENTO),
            ],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
