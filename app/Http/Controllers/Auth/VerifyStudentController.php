<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentResource;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VerifyStudentController extends Controller
{
    /**
     * Verify student identity using matric_number, dob, and school_id
     */
    public function verifyIdentity(Request $request)
    {
        $validated = $request->validate([
            'matric_number' => 'required|string',
            'dob' => 'required|date_format:Y-m-d',
            'school_id' => 'required|exists:schools,id',
        ]);

        $student = Student::where('school_id', $validated['school_id'])
            ->where('dob', $validated['dob'])
            ->where(function ($query) use ($validated) {
                $query->where('matric_number', $validated['matric_number'])
                      ->orWhereRaw('LOWER(matric_number) = ?', [strtolower($validated['matric_number'])]);
            })
            ->first();

        if (!$student) {
            throw ValidationException::withMessages([
                'matric_number' => ['Student identity could not be verified with the provided details.'],
            ]);
        }

        return response()->json([
            'message' => 'Identity verified successfully.',
            'verified' => true,
            'student' => StudentResource::make($student),
        ]);
    }

}
