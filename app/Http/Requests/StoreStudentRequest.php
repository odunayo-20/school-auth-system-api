<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
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
        // 'name' => 'required|string|max:255',
        // 'email' => 'required|email|unique:students,email',
        'user_id' => 'required|exists:users,id',
        'phone' => 'nullable|string|max:20',
        'department_id' => 'required|exists:departments,id',
        'faculty_id' => 'required|exists:faculties,id',
        'school_id' => 'required|exists:schools,id',
        'dob' => 'required|date',
        'matric_number' => 'nullable|string|max:255|unique:students,matric_number',
    ];
}

public function messages(): array
{
    return [
        'user_id.exists' => 'The selected user does not exist.',
        'phone.unique' => 'The phone number has already been taken.',
        'matric_number.unique' => 'The matric number has already been taken.',
        'department_id.exists' => 'The selected department does not exist.',
        'faculty_id.exists' => 'The selected faculty does not exist.',
        'school_id.exists' => 'The selected school does not exist.',
        'dob.required' => 'Date of birth is required.',
        'dob.date' => 'Date of birth must be a valid date.',
    ];
}
}
