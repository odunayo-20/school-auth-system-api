<?php

use App\Models\Department;
use App\Models\Faculty;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

describe('Password Reset', function () {

    beforeEach(function () {
        Mail::fake();

        $this->school = School::create(['name' => 'School of Science', 'code' => 'SCI01']);
        $this->faculty = Faculty::create(['name' => 'Faculty of Science', 'school_id' => $this->school->id]);
        $this->department = Department::create(['name' => 'Computer Science', 'faculty_id' => $this->faculty->id]);

        $this->user = User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('oldpassword123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        $this->student = Student::create([
            'user_id' => $this->user->id,
            'matric_number' => 'CSC/2026/001',
            'phone' => '08105219630',
            'school_id' => $this->school->id,
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'level' => '100',
            'dob' => '2000-01-01',
            'confirmed_at' => Carbon::now(),
        ]);
    });

    it('can request password reset via email', function () {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'student@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Password reset code has been sent to your email address.',
                'email' => 'student@example.com'
            ]);

        Mail::assertQueued(\App\Mail\SendPasswordResetCode::class, function ($mail) {
            return $mail->user->id === $this->user->id;
        });

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'student@example.com',
        ]);
    });

    it('can request password reset via matric_number', function () {
        $response = $this->postJson('/api/auth/forgot-password', [
            'matric_number' => 'CSC/2026/001',
        ]);

        $response->assertStatus(200);

        Mail::assertQueued(\App\Mail\SendPasswordResetCode::class);
    });

    it('can verify a valid reset code', function () {
        // Generate a code and store it
        $code = '123456';
        DB::table('password_reset_tokens')->insert([
            'email' => 'student@example.com',
            'token' => Hash::make($code),
            'created_at' => Carbon::now()
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'student@example.com',
            'code' => $code,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'verified' => true
            ]);
    });

    it('fails to verify an invalid reset code', function () {
        DB::table('password_reset_tokens')->insert([
            'email' => 'student@example.com',
            'token' => Hash::make('123456'),
            'created_at' => Carbon::now()
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'student@example.com',
            'code' => '999999',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    });

    it('fails to verify an expired reset code', function () {
        DB::table('password_reset_tokens')->insert([
            'email' => 'student@example.com',
            'token' => Hash::make('123456'),
            'created_at' => Carbon::now()->subMinutes(20) // Expired
        ]);

        $response = $this->postJson('/api/auth/verify-reset-code', [
            'email' => 'student@example.com',
            'code' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    });

    it('can reset password successfully', function () {
        DB::table('password_reset_tokens')->insert([
            'email' => 'student@example.com',
            'token' => Hash::make('123456'),
            'created_at' => Carbon::now()
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'student@example.com',
            'code' => '123456',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);

        // Verify password was changed
        $this->assertTrue(Hash::check('newpassword123', $this->user->fresh()->password));

        // Verify token was deleted
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'student@example.com'
        ]);
    });
});
