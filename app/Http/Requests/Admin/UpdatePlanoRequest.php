<?php

namespace App\Http\Requests\Admin;

use App\Models\Plano;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $plano = $this->route('plano');

        return $plano instanceof Plano
            && ! $plano->ePlanoSistema()
            && $this->user()?->can('administrar-planos') === true;
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
