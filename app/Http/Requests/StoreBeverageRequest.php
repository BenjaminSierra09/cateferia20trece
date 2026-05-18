<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBeverageRequest extends FormRequest
{
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
            'beverage_category_id' => ['nullable', 'integer', 'exists:beverage_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'is_hot' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'size_prices' => ['required', 'array', 'min:1'],
            'size_prices.*.size_id' => ['required', 'integer', 'exists:sizes,id', 'distinct'],
            'size_prices.*.price' => ['required', 'numeric', 'min:0'],
            'size_prices.*.is_active' => ['sometimes', 'boolean'],
            'customization_option_ids' => ['nullable', 'array'],
            'customization_option_ids.*' => ['integer', 'exists:customization_options,id'],
        ];
    }
}
