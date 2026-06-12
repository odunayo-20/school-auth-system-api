<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendPasswordResetCode;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Generate a 6-digit code
     */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Find user by email or matric_number
     */
    private function findUser(Request $request): ?User
    {
        $user = null;
        
        if ($request->filled('email')) {
            $user = User::where('email', $request->input('email'))->first();
        }

        if (!$user) {
            $identifier = $request->input('email') ?? $request->input('matric_number');
            if ($identifier) {
                $student = Student::where('matric_number', $identifier)
                    ->orWhereRaw('LOWER(matric_number) = ?', [strtolower($identifier)])
                    ->first();

                if ($student) {
                    $user = $student->user;
                }
            }
        }
        
        return $user;
    }

    /**
     * Request a password reset code
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required_without:matric_number|string',
            'matric_number' => 'required_without:email|string',
        ]);

        $user = $this->findUser($request);
        $errorKey = $request->has('matric_number') ? 'matric_number' : 'email';

        if (!$user) {
            throw ValidationException::withMessages([
                $errorKey => 'We could not find a user with that identifier.',
            ]);
        }

        $code = $this->generateCode();

        // Update or insert into password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($code),
                'created_at' => Carbon::now()
            ]
        );

        // Send email
        Mail::queue(new SendPasswordResetCode($user, $code));

        return response()->json([
            'message' => 'Password reset code has been sent to your email address.',
            'email' => $user->email // Returning email so frontend knows where it was sent (helpful if they used matric_number)
        ]);
    }

    /**
     * Verify the 6-digit reset code
     */
    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required_without:matric_number|string',
            'matric_number' => 'required_without:email|string',
            'code' => 'required|string|digits:6',
        ]);

        $user = $this->findUser($request);
        $errorKey = $request->has('matric_number') ? 'matric_number' : 'email';

        if (!$user) {
            throw ValidationException::withMessages([
                $errorKey => 'We could not find a user with that identifier.',
            ]);
        }

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'code' => 'Invalid or expired password reset code.',
            ]);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'Password reset code has expired.',
            ]);
        }

        if (!Hash::check($request->input('code'), $record->token)) {
            throw ValidationException::withMessages([
                'code' => 'Invalid password reset code.',
            ]);
        }

        return response()->json([
            'message' => 'Code verified successfully. You can now reset your password.',
            'verified' => true
        ]);
    }

    /**
     * Reset the password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required_without:matric_number|string',
            'matric_number' => 'required_without:email|string',
            'code' => 'required|string|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->findUser($request);
        $errorKey = $request->has('matric_number') ? 'matric_number' : 'email';

        if (!$user) {
            throw ValidationException::withMessages([
                $errorKey => 'We could not find a user with that identifier.',
            ]);
        }

        $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'code' => 'Invalid or expired password reset code.',
            ]);
        }

        if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
            throw ValidationException::withMessages([
                'code' => 'Password reset code has expired.',
            ]);
        }

        if (!Hash::check($request->input('code'), $record->token)) {
            throw ValidationException::withMessages([
                'code' => 'Invalid password reset code.',
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        // Delete the token
        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        // Optionally, invalidate all existing sessions/tokens
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password has been reset successfully. You can now login with your new password.',
        ]);
    }
}
