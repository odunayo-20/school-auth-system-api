<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
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
    $student = $this->route('student');

    return [
            'user_id' => ['required', 'exists:users,id'],
            'school_id' => ['required', 'exists:schools,id'],
            'faculty_id' => ['required', 'exists:faculties,id'],
        'phone' => ['nullable', 'string', 'max:20'],
        'department_id' => ['required', 'exists:departments,id'],
        'dob' => ['required', 'date'],
        'matric_number' => [
            'nullable',
            'string',
            'max:255',
            Rule::unique('students', 'matric_number')->ignore($student?->id),
        ],
    ];
}

public function messages(): array
{
    return [
        'dob.required' => 'Date of birth is required.',
        'dob.date' => 'Date of birth must be a valid date.',
        'matric_number.unique' => 'The matric number has already been taken.',
        'department_id.exists' => 'The selected department does not exist.',
        'faculty_id.exists' => 'The selected faculty does not exist.',
        'school_id.exists' => 'The selected school does not exist.',
        'user_id.exists' => 'The selected user does not exist.',
    ];
}
}
