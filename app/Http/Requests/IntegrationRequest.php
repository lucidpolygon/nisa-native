<?php

namespace App\Http\Requests;

use App\Models\Integration;
use Illuminate\Foundation\Http\FormRequest;

class IntegrationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:100',
            'type' => 'required|string|in:' . implode(',', Integration::TYPES),
            'active' => 'boolean',
        ];

        // Require encrypted_value on create, optional on update
        if ($this->isMethod('post')) {
            $rules['encrypted_value'] = 'required|array';
        } else {
            $rules['encrypted_value'] = 'sometimes|array';
        }

        return $rules;
    }

}
