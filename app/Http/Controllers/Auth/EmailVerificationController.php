<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    /**
     * Verify email with code
     */
    public function verifyEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|digits:6',
        ]);

        $user = User::where('email', $validated['email'])->firstOrFail();

        // Check if code is expired
        if ($user->email_verification_code_expires_at && $user->email_verification_code_expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'Verification code has expired.',
            ]);
        }

        // Check if code matches
        if (!Hash::check($validated['code'], $user->email_verification_code)) {
            throw ValidationException::withMessages([
                'code' => 'Invalid verification code.',
            ]);
        }

        // Mark email as verified
        $user->update([
            'email_verified_at' => Carbon::now(),
            'email_verification_code' => null,
            'email_verification_code_expires_at' => null,
        ]);

        return response()->json([
            'message' => 'Email verified successfully. Next, complete your student profile.',
            'user' => UserResource::make($user),
            'next_step' => 'confirm_student',
        ]);
    }
}
