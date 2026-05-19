<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreVoiceSaleDraftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'audio' => [
                'required',
                File::types(['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'ogg', 'wav', 'webm'])
                    ->max(25 * 1024),
            ],
            'customer_uuid' => ['nullable', 'uuid'],
            'language' => ['nullable', 'string', 'size:2'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
