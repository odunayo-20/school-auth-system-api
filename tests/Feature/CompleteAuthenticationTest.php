<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

describe('Authentication System', function () {

    describe('Registration', function () {

        it('allows users to register with valid data', function () {
            Mail::fake();

            $response = $this->postJson('/api/auth/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            expect($response->status())->toBe(201);
            expect($response->json('user.email'))->toBe('john@example.com');
            $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        });

        it('prevents registration with duplicate email', function () {
            User::create([
                'name' => 'Existing User',
                'email' => 'existing@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]);

            $response = $this->postJson('/api/auth/register', [
                'name' => 'John Doe',
                'email' => 'existing@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.email'))->toHaveCount(1);
        });

        it('requires password confirmation', function () {
            $response = $this->postJson('/api/auth/register', [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => 'password123',
                'password_confirmation' => 'different',
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.password'))->toHaveCount(1);
        });
    });

    describe('Email Verification', function () {

        it('allows users to verify email with correct code', function () {
            $code = '123456';
            $user = User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'email_verification_code' => Hash::make($code),
                'email_verification_code_expires_at' => Carbon::now()->addMinutes(15),
            ]);

            $response = $this->postJson('/api/auth/verify-email', [
                'email' => 'john@example.com',
                'code' => $code,
            ]);

            expect($response->status())->toBe(200);
            expect($user->fresh()->email_verified_at)->not->toBeNull();
        });

        it('rejects invalid verification code', function () {
            User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'email_verification_code' => Hash::make('123456'),
                'email_verification_code_expires_at' => Carbon::now()->addMinutes(15),
            ]);

            $response = $this->postJson('/api/auth/verify-email', [
                'email' => 'john@example.com',
                'code' => '999999',
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.code'))->toHaveCount(1);
        });

        it('rejects expired verification code', function () {
            User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'email_verification_code' => Hash::make('123456'),
                'email_verification_code_expires_at' => Carbon::now()->subMinutes(1),
            ]);

            $response = $this->postJson('/api/auth/verify-email', [
                'email' => 'john@example.com',
                'code' => '123456',
            ]);

            expect($response->status())->toBe(422);
        });
    });

    describe('Sign-In Code', function () {

        it('allows verified users to request sign-in code', function () {
            Mail::fake();

            User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'email_verified_at' => Carbon::now(),
            ]);

            $response = $this->postJson('/api/auth/request-signin-code', [
                'email' => 'john@example.com',
            ]);

            expect($response->status())->toBe(200);
            expect($response->json('message'))->toContain('sent to your email');
        });

        it('prevents unverified users from requesting sign-in code', function () {
            User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]);

            $response = $this->postJson('/api/auth/request-signin-code', [
                'email' => 'john@example.com',
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.email'))->toHaveCount(1);
        });

        it('allows users to sign in with valid code', function () {
            $code = '654321';
            User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'email_verified_at' => Carbon::now(),
                'signin_code' => Hash::make($code),
                'signin_code_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            $response = $this->postJson('/api/auth/signin-with-code', [
                'email' => 'john@example.com',
                'code' => $code,
            ]);

            expect($response->status())->toBe(200);
            expect($response->json('token'))->not->toBeNull();
        });

        it('rejects invalid sign-in code', function () {
            User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'email_verified_at' => Carbon::now(),
                'signin_code' => Hash::make('654321'),
                'signin_code_expires_at' => Carbon::now()->addMinutes(10),
            ]);

            $response = $this->postJson('/api/auth/signin-with-code', [
                'email' => 'john@example.com',
                'code' => '999999',
            ]);

            expect($response->status())->toBe(422);
        });
    });

    describe('Login', function () {

        it('allows verified users to login with correct credentials', function () {
            // Use admin role — the login endpoint requires student confirmation for student role
            User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => Carbon::now(),
            ]);

            $response = $this->postJson('/api/auth/login', [
                'email' => 'john@example.com',
                'password' => 'password123',
            ]);

            expect($response->status())->toBe(200);
            expect($response->json('token'))->not->toBeNull();
        });

        it('rejects login with incorrect password', function () {
            User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'email_verified_at' => Carbon::now(),
            ]);

            $response = $this->postJson('/api/auth/login', [
                'email' => 'john@example.com',
                'password' => 'wrongpassword',
            ]);

            expect($response->status())->toBe(422);
        });

        it('prevents unverified users from logging in', function () {
            User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]);

            $response = $this->postJson('/api/auth/login', [
                'email' => 'john@example.com',
                'password' => 'password123',
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.email'))->toHaveCount(1);
        });
    });

    describe('Protected Routes', function () {

        it('authenticated user can view profile', function () {
            // /api/auth/me is behind role:admin middleware
            $user = User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => Carbon::now(),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer $token")
                ->getJson('/api/auth/me');

            expect($response->status())->toBe(200);
            expect($response->json('data.email'))->toBe('john@example.com');
        });

        it('authenticated user can logout', function () {
            // /api/auth/logout is behind role:admin middleware
            $user = User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'email_verified_at' => Carbon::now(),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            $response = $this->withHeader('Authorization', "Bearer $token")
                ->postJson('/api/auth/logout');

            expect($response->status())->toBe(200);
            expect($response->json('message'))->toContain('Logged out');
        });

        it('unauthenticated user cannot access protected route', function () {
            $response = $this->getJson('/api/auth/me');

            expect($response->status())->toBe(401);
        });
    });
});
