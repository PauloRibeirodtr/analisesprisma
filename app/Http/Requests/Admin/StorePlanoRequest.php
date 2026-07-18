<?php

namespace App\Http\Requests\Admin;

use App\Models\Plano;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlanoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('administrar-planos') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn([Plano::SLUG_TESTE]),
                Rule::unique(Plano::class, 'slug'),
            ],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'valor_padrao' => [
                'required',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:99999999.99',
            ],
            'duracao_dias' => [
                'required',
                'integer',
                'min:1',
                'max:3650',
            ],
            'disponivel_para_venda' => ['required', 'boolean'],
        ];
    }
}
