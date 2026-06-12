<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Student;
use App\Http\Resources\UserResource;

class LoginController extends Controller
{
    /**
     * Login with email and password
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required_without:matric_number|string',
            'matric_number' => 'required_without:email|string',
            'password' => 'required|string',
        ]);

        $user = null;
        $errorKey = $request->has('matric_number') ? 'matric_number' : 'email';

        if ($request->filled('email')) {
            $user = User::where('email', $request->input('email'))->first();
        }

        if (!$user) {
            $identifier = $request->input('email') ?? $request->input('matric_number');
            $student = Student::where('matric_number', $identifier)
                ->orWhereRaw('LOWER(matric_number) = ?', [strtolower($identifier)])
                ->first();

            if ($student) {
                $user = $student->user;
            }
        }

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                $errorKey => 'Invalid credentials.',
            ]);
        }

        // Check if email is verified
        if (!$user->email_verified_at) {
            throw ValidationException::withMessages([
                $errorKey => 'Please verify your email first.',
            ]);
        }

        // Check if student is confirmed (for student role)
        if ($user->isStudent()) {
            $student = Student::where('user_id', $user->id)->first();
            if (!$student || !$student->confirmed_at) {
                throw ValidationException::withMessages([
                    $errorKey => 'Please complete your student profile confirmation.',
                ]);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'user' => UserResource::make($user),
            'token' => $token,
        ]);
    }

    /**
     * Logout (revoke token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
