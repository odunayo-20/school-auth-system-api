<?php

use App\Models\Department;
use App\Models\Faculty;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

describe('Student Confirmation', function () {

    beforeEach(function () {
        Mail::fake();

        // Create school, faculty, department
        $this->school = School::create(['name' => 'School of Science', 'code' => 'SCI01']);
        $this->faculty = Faculty::create(['name' => 'Faculty of Science', 'school_id' => $this->school->id]);
        $this->department = Department::create(['name' => 'Computer Science', 'faculty_id' => $this->faculty->id]);
    });

    it('allows verified students to confirm their profile', function () {
        $user = User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/confirm-student', [
            'email' => 'student@example.com',
            'phone' => '08105219630',
            'matric_number' => 'CSC001',
            'school_id' => $this->school->id,
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'level' => '100',
            'dob' => '2000-01-01',
        ]);

        expect($response->status())->toBe(201);
        expect($response->json('student.confirmed_at'))->not->toBeNull();
        expect($response->json('student.matric_number'))->toBe('CSC001');
        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'matric_number' => 'CSC001',
            'phone' => '08105219630',
        ]);
    });

    it('prevents unverified students from confirming their profile', function () {
        $user = User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => null, // Not verified
        ]);

        $response = $this->postJson('/api/auth/confirm-student', [
            'email' => 'student@example.com',
            'phone' => '08105219630',
            'matric_number' => 'CSC001',
            'school_id' => $this->school->id,
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'dob' => '2000-01-01',
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors.email'))->not->toBeEmpty();
    });

    it('prevents non-students from confirming a student profile', function () {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/confirm-student', [
            'email' => 'admin@example.com',
            'phone' => '08105219630',
            'matric_number' => 'CSC001',
            'school_id' => $this->school->id,
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'dob' => '2000-01-01',
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors.email'))->not->toBeEmpty();
    });

    it('requires unique matric number', function () {
        $user1 = User::create([
            'name' => 'Student 1',
            'email' => 'student1@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        Student::create([
            'user_id' => $user1->id,
            'matric_number' => 'CSC001',
            'phone' => '08105219630',
            'school_id' => $this->school->id,
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'level' => '100',
            'dob' => '2000-01-01',
            'confirmed_at' => Carbon::now(),
        ]);

        $user2 = User::create([
            'name' => 'Student 2',
            'email' => 'student2@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/confirm-student', [
            'email' => 'student2@example.com',
            'phone' => '08123456789',
            'matric_number' => 'CSC001', // Duplicate
            'school_id' => $this->school->id,
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'dob' => '2000-01-01',
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors.matric_number'))->not->toBeEmpty();
    });

    it('prevents double confirmation of student profile', function () {
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

        $response = $this->postJson('/api/auth/confirm-student', [
            'email' => 'student@example.com',
            'phone' => '08105219630',
            'matric_number' => 'CSC001',
            'school_id' => $this->school->id,
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'dob' => '2000-01-01',
        ]);

        expect($response->status())->toBe(422);
    });

    it('validates required fields', function () {
        User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/confirm-student', [
            'email' => 'student@example.com',
            // Missing other required fields
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors'))->toHaveKeys(['phone', 'school_id', 'faculty_id', 'department_id', 'dob']);
    });

    it('validates that school, faculty, and department exist', function () {
        User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/confirm-student', [
            'email' => 'student@example.com',
            'phone' => '08105219630',
            'matric_number' => 'CSC001',
            'school_id' => 9999, // Non-existent
            'faculty_id' => $this->faculty->id,
            'department_id' => $this->department->id,
            'dob' => '2000-01-01',
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors.school_id'))->not->toBeEmpty();
    });
});
