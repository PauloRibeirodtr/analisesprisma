<?php

namespace App\Http\Requests\Admin;

use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRenovacaoRequest extends FormRequest
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
            'dias_adicionados' => [
                'required',
                'integer',
                'min:1',
                'max:3650',
            ],
            'valor_recebido' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
            ],
            'data_pagamento' => ['required', 'date'],
            'forma_pagamento' => [
                'required',
                'string',
                Rule::in(Pagamento::FORMAS_PAGAMENTO),
            ],
            'nova_data_inicio' => [
                Rule::requiredIf(
                    $this->route('assinatura')?->estaVencida() === true,
                ),
                'nullable',
                'date',
            ],
            'observacao' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
