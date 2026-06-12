<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentConfirmationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Public endpoint, authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
{
    return [
        'email' => 'required|email|exists:users,email',
        'phone' => 'required|string|max:20',

        'matric_number' => [
            'nullable',
            'string',
            Rule::unique('students', 'matric_number')
            //     ->where(fn ($query) => $query->where('email', $this->input('email'))),
        ],

        'school_id' => 'required|exists:schools,id',
        'faculty_id' => 'required|exists:faculties,id',
        'department_id' => 'required|exists:departments,id',
        'level' => 'nullable|string|max:50',
        'dob' => 'required|date',
    ];
}


    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be valid.',
            'email.exists' => 'Email not found. Please register first.',
            'phone.required' => 'Phone number is required.',
            'matric_number.unique' => 'This matric number is already taken.',
            'school_id.required' => 'School is required.',
            'school_id.exists' => 'Selected school does not exist.',
            'faculty_id.required' => 'Faculty is required.',
            'faculty_id.exists' => 'Selected faculty does not exist.',
            'department_id.required' => 'Department is required.',
            'department_id.exists' => 'Selected department does not exist.',
            'dob.required' => 'Date of birth is required.',
            'dob.date' => 'Date of birth must be a valid date.',
        ];
    }
}
