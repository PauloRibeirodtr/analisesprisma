<?php

namespace App\Http\Requests\Admin;

use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReativacaoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $cliente = $this->route('cliente');
        $assinatura = $this->route('assinatura');

        return $cliente instanceof User
            && $cliente->perfil === 'cliente'
            && $assinatura instanceof Assinatura
            && $assinatura->user_id === $cliente->id
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
            'confirmada' => ['required', 'accepted'],
            'inicio_em' => ['required', 'date'],
            'duracao_dias' => [
                'required',
                'integer',
                'min:1',
                'max:3650',
            ],
            'registrar_pagamento' => ['required', 'boolean'],
            'valor_recebido' => [
                'required_if:registrar_pagamento,1',
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0.01',
            ],
            'data_pagamento' => [
                'required_if:registrar_pagamento,1',
                'nullable',
                'date',
            ],
            'forma_pagamento' => [
                'required_if:registrar_pagamento,1',
                'nullable',
                'string',
                Rule::in(Pagamento::FORMAS_PAGAMENTO),
            ],
            'observacao' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ];
    }
}
