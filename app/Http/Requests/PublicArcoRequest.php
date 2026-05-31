<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicArcoRequest extends FormRequest
{
    /**
     * ARCO rights (plus consent revocation) a data subject may exercise.
     *
     * @var array<int, string>
     */
    public const RIGHTS = ['acceso', 'rectificacion', 'cancelacion', 'oposicion', 'revocacion'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'derechos' => ['required', 'array', 'min:1'],
            'derechos.*' => ['string', Rule::in(self::RIGHTS)],
            'cuenta_identificador' => ['nullable', 'string', 'max:255'],
            'detalle' => ['required', 'string', 'min:10', 'max:5000'],
            'identidad_consent' => ['accepted'],
            // Honeypot: real users leave this empty; bots tend to fill it.
            'website' => ['nullable', 'max:0'],
        ];
    }

    /**
     * Human-friendly attribute names for validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre completo',
            'email' => 'correo electrónico',
            'telefono' => 'teléfono',
            'derechos' => 'derechos a ejercer',
            'cuenta_identificador' => 'dato de identificación de la cuenta',
            'detalle' => 'detalle de la solicitud',
            'identidad_consent' => 'declaración de identidad',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'derechos.required' => 'Selecciona al menos un derecho ARCO que deseas ejercer.',
            'identidad_consent.accepted' => 'Debes confirmar que la información es veraz y que acreditarás tu identidad.',
            'website.max' => 'No fue posible procesar la solicitud.',
        ];
    }
}
