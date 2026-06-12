<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StudentConfirmationRequest;
use App\Http\Resources\StudentResource;
use App\Http\Resources\UserResource;
use App\Models\Student;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

class ConfirmStudentController extends Controller
{
    /**
     * Confirm student and update student profile
     */
    public function confirmStudent(StudentConfirmationRequest $request)
    {
        // Get user by email
        $user = User::where('email', $request->email)->firstOrFail();

        // Verify user is a student
        if (!$user->isStudent()) {
            throw ValidationException::withMessages([
                'email' => 'Only student accounts can confirm a student profile.',
            ]);
        }

        // Verify email is verified first
        if (!$user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => 'Please verify your email first before confirming your student profile.',
            ]);
        }

        // Get or create student record
        $student = Student::where('user_id', $user->id)->first();

        if (!$student) {
            $student = Student::create([
                'user_id' => $user->id,
                'phone' => $request->phone,
                'matric_number' => $request->matric_number,
                'school_id' => $request->school_id,
                'faculty_id' => $request->faculty_id,
                'department_id' => $request->department_id,
                'level' => $request->level,
                'dob' => $request->dob,
                'confirmed_at' => Carbon::now(),
            ]);
        } else {
            // Check if already confirmed
            if ($student->confirmed_at) {
                throw ValidationException::withMessages([
                    'email' => 'Your student profile is already confirmed.',
                ]);
            }

            // Update existing student record
            $student->update([
                'phone' => $request->phone,
                'matric_number' => $request->matric_number,
                'school_id' => $request->school_id,
                'faculty_id' => $request->faculty_id,
                'department_id' => $request->department_id,
                'level' => $request->level,
                'dob' => $request->dob,
                'confirmed_at' => Carbon::now(),
            ]);
        }

        return response()->json([
            'message' => 'Student profile confirmed successfully. You can now login.',
            'user' => UserResource::make($user),
            'student' => StudentResource::make($student),
        ], 201);
    }
}
