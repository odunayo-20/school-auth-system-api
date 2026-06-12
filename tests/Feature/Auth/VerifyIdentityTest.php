<?php

use App\Models\Department;
use App\Models\Faculty;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

describe('Verify Identity', function () {

    beforeEach(function () {
        // Create school, faculty, department
        $this->school = School::create(['name' => 'School of Science', 'code' => 'SCI01']);
        $this->faculty = Faculty::create(['name' => 'Faculty of Science', 'school_id' => $this->school->id]);
        $this->department = Department::create(['name' => 'Computer Science', 'faculty_id' => $this->faculty->id]);

        $this->user = User::create([
            'name' => 'John Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password123'),
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

    it('verifies a student identity successfully with correct details', function () {
        $response = $this->postJson('/api/auth/verify-identity', [
            'matric_number' => 'CSC/2026/001',
            'dob' => '2000-01-01',
            'school_id' => $this->school->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Identity verified successfully.',
                'verified' => true,
            ]);

        expect($response->json('student.id'))->toBe($this->student->id);
        expect($response->json('student.matric_number'))->toBe('CSC/2026/001');
    });

    it('verifies successfully with case-insensitive matric number', function () {
        $response = $this->postJson('/api/auth/verify-identity', [
            'matric_number' => 'csc/2026/001', // Lowercase
            'dob' => '2000-01-01',
            'school_id' => $this->school->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'verified' => true,
            ]);
    });

    it('fails validation when required fields are missing', function () {
        $response = $this->postJson('/api/auth/verify-identity', [
            // Missing all fields
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['matric_number', 'dob', 'school_id']);
    });

    it('fails validation with invalid date format', function () {
        $response = $this->postJson('/api/auth/verify-identity', [
            'matric_number' => 'CSC/2026/001',
            'dob' => 'invalid-date-format',
            'school_id' => $this->school->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dob']);
    });

    it('fails validation when school_id does not exist', function () {
        $response = $this->postJson('/api/auth/verify-identity', [
            'matric_number' => 'CSC/2026/001',
            'dob' => '2000-01-01',
            'school_id' => 9999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['school_id']);
    });

    it('fails verification when matric number or date of birth is incorrect', function () {
        // Wrong matric number
        $response1 = $this->postJson('/api/auth/verify-identity', [
            'matric_number' => 'CSC/2026/999',
            'dob' => '2000-01-01',
            'school_id' => $this->school->id,
        ]);

        $response1->assertStatus(422)
            ->assertJsonValidationErrors(['matric_number']);

        // Wrong dob
        $response2 = $this->postJson('/api/auth/verify-identity', [
            'matric_number' => 'CSC/2026/001',
            'dob' => '1999-12-31',
            'school_id' => $this->school->id,
        ]);

        $response2->assertStatus(422)
            ->assertJsonValidationErrors(['matric_number']);
    });

});
