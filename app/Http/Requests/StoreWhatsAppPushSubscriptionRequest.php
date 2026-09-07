<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWhatsAppPushSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->is_active && $user->canManageWhatsApp();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'endpoint' => ['required', 'string', 'url', 'starts_with:https://', 'max:500'],
            'keys' => ['required', 'array:p256dh,auth'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'content_encoding' => ['required', 'string', Rule::in(['aesgcm', 'aes128gcm'])],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $host = parse_url($this->string('endpoint')->toString(), PHP_URL_HOST);

                if (! is_string($host) || $host === '') {
                    return;
                }

                $isLocalHost = $host === 'localhost' || str_ends_with($host, '.localhost');
                $isNonPublicIp = filter_var($host, FILTER_VALIDATE_IP) !== false
                    && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

                if ($isLocalHost || $isNonPublicIp) {
                    $validator->errors()->add('endpoint', 'El proveedor de notificaciones no es válido.');
                }
            },
        ];
    }
}
