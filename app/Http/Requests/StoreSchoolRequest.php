<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:schools,code',
        ];
    }


    public function messages(): array
    {
        return parent::messages() + [
            'name.required' => 'School name is required.',
            'name.max' => 'School name cannot exceed 255 characters.',
            'code.required' => 'School code is required.',
            'code.unique' => 'School code already exists.',
        ];
    }
}
