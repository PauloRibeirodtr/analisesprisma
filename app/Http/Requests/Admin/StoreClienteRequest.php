<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $usuario = $this->user();
        $perfil = $this->input('perfil');

        return $usuario?->can('administrar-clientes') === true
            && (
                $usuario->can('administrar-perfis-administrativos')
                || $perfil === null
                || $perfil === 'cliente'
            );
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'cpf_cnpj' => ['nullable', 'string', 'max:18'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'perfil' => [
                'required',
                Rule::in($this->perfisPermitidos()),
            ],
            'password' => [
                'required',
                'string',
                Password::default(),
                'confirmed',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function perfisPermitidos(): array
    {
        if ($this->user()?->can('administrar-perfis-administrativos') === true) {
            return ['administrador', 'analista', 'cliente'];
        }

        return ['cliente'];
    }
}
