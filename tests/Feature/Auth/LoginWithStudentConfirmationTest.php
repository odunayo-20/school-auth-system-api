<?php

use App\Models\Department;
use App\Models\Faculty;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

describe('Login with Student Confirmation', function () {

    beforeEach(function () {
        $this->school = School::create(['name' => 'School of Science', 'code' => 'SCI01']);
        $this->faculty = Faculty::create(['name' => 'Faculty of Science', 'school_id' => $this->school->id]);
        $this->department = Department::create(['name' => 'Computer Science', 'faculty_id' => $this->faculty->id]);
    });

    it('prevents unconfirmed students from logging in', function () {
        $user = User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'student@example.com',
            'password' => 'password123',
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors.email'))->not->toBeEmpty();
    });

    it('allows confirmed students to login', function () {
        $user = User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        Student::create([
            'user_id' => $user->id,
            'matric_number' => 'CSC001',
            'phone' => '08105219630',
            'school_id' => $this->school->id,
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'level' => '100',
            'dob' => '2000-01-01',
            'confirmed_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'student@example.com',
            'password' => 'password123',
        ]);

        expect($response->status())->toBe(200);
        expect($response->json('token'))->not->toBeNull();
        expect($response->json('user.role'))->toBe('student');
    });

    it('allows non-student roles to login without confirmation', function () {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        expect($response->status())->toBe(200);
        expect($response->json('token'))->not->toBeNull();
        expect($response->json('user.role'))->toBe('admin');
    });

    it('prevents unverified emails from logging in', function () {
        User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => null,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'student@example.com',
            'password' => 'password123',
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors.email.0'))->toContain('verify your email');
    });

    it('requires valid credentials', function () {
        User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'student@example.com',
            'password' => 'wrongpassword',
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors.email'))->not->toBeEmpty();
    });

    it('allows confirmed students to login using matric_number parameter', function () {
        $user = User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        Student::create([
            'user_id' => $user->id,
            'matric_number' => 'CSC001',
            'phone' => '08105219630',
            'school_id' => $this->school->id,
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'level' => '100',
            'dob' => '2000-01-01',
            'confirmed_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'matric_number' => 'CSC001',
            'password' => 'password123',
        ]);

        expect($response->status())->toBe(200);
        expect($response->json('token'))->not->toBeNull();
        expect($response->json('user.role'))->toBe('student');
    });

    it('allows confirmed students to login using matric_number in email parameter', function () {
        $user = User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        Student::create([
            'user_id' => $user->id,
            'matric_number' => 'CSC001',
            'phone' => '08105219630',
            'school_id' => $this->school->id,
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'level' => '100',
            'dob' => '2000-01-01',
            'confirmed_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'CSC001', // Passing matric number in email field
            'password' => 'password123',
        ]);

        expect($response->status())->toBe(200);
        expect($response->json('token'))->not->toBeNull();
        expect($response->json('user.role'))->toBe('student');
    });

    it('fails login with invalid matric_number', function () {
        $response = $this->postJson('/api/auth/login', [
            'matric_number' => 'INVALID_MATRIC',
            'password' => 'password123',
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors.matric_number'))->not->toBeEmpty();
    });
});
