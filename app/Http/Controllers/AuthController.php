<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentConfirmationRequest;
use App\Http\Resources\StudentResource;
use App\Http\Resources\UserResource;
use App\Mail\SendSignInCode;
use App\Mail\SendVerificationCode;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user and send verification email
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create user
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => 'student', // Default role
            'password' => Hash::make($validated['password']),
        ]);

        // Generate verification code
        $verificationCode = $this->generateCode();
        $user->update([
            'email_verification_code' => Hash::make($verificationCode),
            'email_verification_code_expires_at' => Carbon::now()->addMinutes(15),
        ]);

        // Queue verification email (async)
        Mail::queue(new SendVerificationCode($user, $verificationCode));

        return response()->json([
            'message' => 'User registered successfully. Please verify your email.',
            'user' => UserResource::make($user),
            'verification_code' => $verificationCode,
        ], 201);
    }



    /**
     * Request a sign-in code
     */
    public function requestSignInCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        // Check if user email is verified
        if (!$user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => 'Please verify your email first.',
            ]);
        }

        // Generate sign-in code
        $signinCode = $this->generateCode();
        $user->update([
            'signin_code' => Hash::make($signinCode),
            'signin_code_expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Queue sign-in email (async)
        Mail::queue(new SendSignInCode($user, $signinCode));

        return response()->json([
            'message' => 'Sign-in code sent to your email.',
        ]);
    }

    /**
     * Sign in with code
     */
    public function signInWithCode(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|digits:6',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        // Check if code is expired
        if ($user->signin_code_expires_at && $user->signin_code_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'Sign-in code has expired.',
            ]);
        }

        // Check if code matches
        if (!Hash::check($validated['code'], $user->signin_code)) {
            throw ValidationException::withMessages([
                'code' => 'Invalid sign-in code.',
            ]);
        }

        // Clear sign-in code
        $user->update([
            'signin_code' => null,
            'signin_code_expires_at' => null,
        ]);

        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Signed in successfully.',
            'user' => UserResource::make($user),
            'token' => $token,
        ]);
    }



    /**
     * Get current user
     */
    public function me(Request $request)
    {
        return UserResource::make($request->user());
    }

    /**
     * Generate a 6-digit code
     */
    private function generateCode(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
