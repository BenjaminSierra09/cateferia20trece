<?php

namespace App\Http\Requests;

use App\Models\Customer;
use App\Services\GoogleRecaptchaVerifier;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PublicCustomerRegistrationRequest extends FormRequest
{
    public const RECAPTCHA_ACTION = 'public_customer_registration';

    protected const PHONE_REGEX = '/^\+[1-9]\d{7,14}$/';

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
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'nullable',
                'required_without:email',
                'string',
                'max:50',
                'regex:'.self::PHONE_REGEX,
                Rule::unique(Customer::class, 'phone'),
            ],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'email' => [
                'nullable',
                'required_without:phone',
                'email',
                'max:255',
                Rule::unique(Customer::class, 'email'),
            ],
            'privacy_consent' => ['accepted'],
            'recaptcha_token' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $verification = app(GoogleRecaptchaVerifier::class)->verify(
                    token: $this->string('recaptcha_token')->toString(),
                    ipAddress: $this->ip(),
                    expectedAction: self::RECAPTCHA_ACTION,
                );

                if (! $verification['successful']) {
                    $validator->errors()->add('recaptcha', $verification['message']);
                }
            },
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Captura el teléfono en formato internacional, por ejemplo +524151234567.',
            'phone.unique' => 'Este teléfono ya está registrado.',
            'email.unique' => 'Este correo ya está registrado.',
            'privacy_consent.accepted' => 'Necesitas aceptar el aviso de privacidad para continuar.',
        ];
    }

    /**
     * Get validation attribute labels.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => 'teléfono',
            'birthday' => 'fecha de nacimiento',
            'email' => 'correo electrónico',
            'privacy_consent' => 'aviso de privacidad',
        ];
    }
}
