<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicInvoiceRequest extends FormRequest
{
    /**
     * SAT c_RegimenFiscal catalog (code => description).
     *
     * @var array<string, string>
     */
    public const REGIMENES = [
        '601' => 'General de Ley Personas Morales',
        '603' => 'Personas Morales con Fines no Lucrativos',
        '605' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios',
        '606' => 'Arrendamiento',
        '607' => 'Régimen de Enajenación o Adquisición de Bienes',
        '608' => 'Demás ingresos',
        '610' => 'Residentes en el Extranjero sin Establecimiento Permanente en México',
        '611' => 'Ingresos por Dividendos (socios y accionistas)',
        '612' => 'Personas Físicas con Actividades Empresariales y Profesionales',
        '614' => 'Ingresos por intereses',
        '615' => 'Régimen de los ingresos por obtención de premios',
        '616' => 'Sin obligaciones fiscales',
        '620' => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos',
        '621' => 'Incorporación Fiscal',
        '622' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
        '623' => 'Opcional para Grupos de Sociedades',
        '624' => 'Coordinados',
        '625' => 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
        '626' => 'Régimen Simplificado de Confianza',
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize fiscal fields before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'rfc' => strtoupper(trim((string) $this->input('rfc'))),
            'codigo_postal' => trim((string) $this->input('codigo_postal')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rfc' => ['required', 'string', 'regex:/^[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}$/'],
            'razon_social' => ['required', 'string', 'max:255'],
            'regimen_fiscal' => ['required', 'string', Rule::in(array_keys(self::REGIMENES))],
            'codigo_postal' => ['required', 'string', 'regex:/^\d{5}$/'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['required', 'string', 'max:50'],
            'numero_venta' => ['required', 'string', 'max:50'],
            // Honeypot: real users leave this empty.
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
            'rfc' => 'RFC',
            'razon_social' => 'razón social',
            'regimen_fiscal' => 'régimen fiscal',
            'codigo_postal' => 'código postal',
            'email' => 'correo electrónico',
            'telefono' => 'teléfono',
            'numero_venta' => 'número de venta',
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
            'rfc.regex' => 'Captura un RFC válido (12 o 13 caracteres).',
            'codigo_postal.regex' => 'El código postal debe tener 5 dígitos.',
            'website.max' => 'No fue posible procesar la solicitud.',
        ];
    }
}
