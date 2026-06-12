<?php

use App\Models\Department;
use App\Models\Faculty;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Student Management', function () {

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /**
     * Returns [school, faculty, department] with one consistent seed set.
     */
    function stdSetup(): array
    {
        $school     = School::create(['name' => 'University of Lagos', 'code' => 'UNILAG']);
        $faculty    = Faculty::create(['name' => 'Engineering', 'school_id' => $school->id]);
        $department = Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);
        return [$school, $faculty, $department];
    }

    function stdAdmin(): User
    {
        return User::create([
            'name'              => 'Admin User',
            'email'             => 'admin@student.test',
            'password'          => Hash::make('password123'),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    function stdStudentUser(): User
    {
        return User::create([
            'name'              => 'Student User',
            'email'             => 'student@student.test',
            'password'          => Hash::make('password123'),
            'role'              => 'student',
            'email_verified_at' => now(),
        ]);
    }

    function createStudentRecord(User $user, array $setup, string $matric = 'MAT/001'): Student
    {
        [$school, $faculty, $department] = $setup;
        return Student::create([
            'user_id'       => $user->id,
            'phone'         => '08012345678',
            'matric_number' => $matric,
            'school_id'     => $school->id,
            'faculty_id'    => $faculty->id,
            'department_id' => $department->id,
            'confirmed_at'  => now(),
        ]);
    }

    // ─── Index ───────────────────────────────────────────────────────────────────

    describe('List Students (GET /api/admin/students)', function () {

        it('allows admin to list all students', function () {
            $admin   = stdAdmin();
            $setup   = stdSetup();
            $user1   = stdStudentUser();
            $user2   = User::create([
                'name'              => 'Student 2',
                'email'             => 'student2@student.test',
                'password'          => Hash::make('password123'),
                'role'              => 'student',
                'email_verified_at' => now(),
            ]);
            createStudentRecord($user1, $setup, 'MAT/001');
            createStudentRecord($user2, $setup, 'MAT/002');

            $response = $this->actingAs($admin)->getJson('/api/admin/students');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(2);
        });

        it('eager loads department, faculty, and school', function () {
            $admin  = stdAdmin();
            $setup  = stdSetup();
            $user   = stdStudentUser();
            createStudentRecord($user, $setup, 'MAT/001');

            $response = $this->actingAs($admin)->getJson('/api/admin/students');

            expect($response->status())->toBe(200);
            $first = $response->json('data.0');
            // StudentResource uses 'department_id', 'faculty_id', 'school_id' as the key names
            expect($first)->toHaveKey('department_id');
            expect($first)->toHaveKey('faculty_id');
            expect($first)->toHaveKey('school_id');
        });

        it('denies non-admin users', function () {
            $student = stdStudentUser();

            $response = $this->actingAs($student)->getJson('/api/admin/students');

            expect($response->status())->toBe(403);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/admin/students');

            expect($response->status())->toBe(401);
        });
    });

    // ─── Store ───────────────────────────────────────────────────────────────────

    describe('Create Student (POST /api/admin/students)', function () {

        it('allows admin to create a student record', function () {
            $admin = stdAdmin();
            $setup = stdSetup();
            [$school, $faculty, $department] = $setup;
            $user  = stdStudentUser();

            $response = $this->actingAs($admin)->postJson('/api/admin/students', [
                'user_id'       => $user->id,
                'phone'         => '08012345678',
                'matric_number' => 'MAT/001',
                'school_id'     => $school->id,
                'faculty_id'    => $faculty->id,
                'department_id' => $department->id,
            ]);

            expect($response->status())->toBe(201);
            $this->assertDatabaseHas('students', [
                'user_id'       => $user->id,
                'matric_number' => 'MAT/001',
            ]);
        });

        it('validates all required fields', function () {
            $admin = stdAdmin();

            $response = $this->actingAs($admin)->postJson('/api/admin/students', []);

            expect($response->status())->toBe(422);
            expect($response->json('errors'))->toHaveKeys([
                'user_id',
                'school_id',
                'faculty_id',
                'department_id',
            ]);
        });

        it('rejects a non-existent user_id', function () {
            $admin = stdAdmin();
            $setup = stdSetup();
            [$school, $faculty, $department] = $setup;

            $response = $this->actingAs($admin)->postJson('/api/admin/students', [
                'user_id'       => 9999,
                'phone'         => '08012345678',
                'matric_number' => 'MAT/001',
                'school_id'     => $school->id,
                'faculty_id'    => $faculty->id,
                'department_id' => $department->id,
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.user_id'))->not->toBeEmpty();
        });

        it('rejects a duplicate matric number', function () {
            $admin = stdAdmin();
            $setup = stdSetup();
            [$school, $faculty, $department] = $setup;
            $user1 = stdStudentUser();
            createStudentRecord($user1, $setup, 'MAT/001');

            $user2 = User::create([
                'name'              => 'Student 2',
                'email'             => 'student2@student.test',
                'password'          => Hash::make('password123'),
                'role'              => 'student',
                'email_verified_at' => now(),
            ]);

            $response = $this->actingAs($admin)->postJson('/api/admin/students', [
                'user_id'       => $user2->id,
                'phone'         => '08099999999',
                'matric_number' => 'MAT/001', // duplicate
                'school_id'     => $school->id,
                'faculty_id'    => $faculty->id,
                'department_id' => $department->id,
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.matric_number'))->not->toBeEmpty();
        });

        it('rejects non-existent school_id', function () {
            $admin = stdAdmin();
            $setup = stdSetup();
            [, $faculty, $department] = $setup;
            $user  = stdStudentUser();

            $response = $this->actingAs($admin)->postJson('/api/admin/students', [
                'user_id'       => $user->id,
                'matric_number' => 'MAT/001',
                'school_id'     => 9999,
                'faculty_id'    => $faculty->id,
                'department_id' => $department->id,
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.school_id'))->not->toBeEmpty();
        });

        it('allows optional phone and dob fields', function () {
            $admin = stdAdmin();
            $setup = stdSetup();
            [$school, $faculty, $department] = $setup;
            $user  = stdStudentUser();

            $response = $this->actingAs($admin)->postJson('/api/admin/students', [
                'user_id'       => $user->id,
                'matric_number' => 'MAT/001',
                'school_id'     => $school->id,
                'faculty_id'    => $faculty->id,
                'department_id' => $department->id,
                // phone and dob intentionally omitted
            ]);

            expect($response->status())->toBe(201);
        });

        it('denies non-admin users', function () {
            $student = stdStudentUser();
            $setup   = stdSetup();
            [$school, $faculty, $department] = $setup;

            $response = $this->actingAs($student)->postJson('/api/admin/students', [
                'user_id'       => $student->id,
                'matric_number' => 'MAT/001',
                'school_id'     => $school->id,
                'faculty_id'    => $faculty->id,
                'department_id' => $department->id,
            ]);

            expect($response->status())->toBe(403);
        });
    });

    // ─── Show ────────────────────────────────────────────────────────────────────

    describe('Show Student (GET /api/admin/students/{id})', function () {

        it('allows admin to view a single student', function () {
            $admin   = stdAdmin();
            $setup   = stdSetup();
            $user    = stdStudentUser();
            $student = createStudentRecord($user, $setup, 'MAT/001');

            $response = $this->actingAs($admin)->getJson("/api/admin/students/{$student->id}");

            expect($response->status())->toBe(200);
            expect($response->json('data.matric_number'))->toBe('MAT/001');
        });

        it('returns 404 for a non-existent student', function () {
            $admin = stdAdmin();

            $response = $this->actingAs($admin)->getJson('/api/admin/students/9999');

            expect($response->status())->toBe(404);
        });

        it('denies non-admin users', function () {
            $studentUser = stdStudentUser();
            $setup       = stdSetup();
            $record      = createStudentRecord($studentUser, $setup, 'MAT/001');

            $response = $this->actingAs($studentUser)->getJson("/api/admin/students/{$record->id}");

            expect($response->status())->toBe(403);
        });
    });

    // ─── Update ──────────────────────────────────────────────────────────────────

    describe('Update Student (PUT /api/admin/students/{id})', function () {

        it('allows admin to update a student record', function () {
            $admin   = stdAdmin();
            $setup   = stdSetup();
            [$school, $faculty, $department] = $setup;
            $user    = stdStudentUser();
            $student = createStudentRecord($user, $setup, 'MAT/001');

            $response = $this->actingAs($admin)->putJson("/api/admin/students/{$student->id}", [
                'user_id'       => $user->id,
                'phone'         => '08099999999',
                'matric_number' => 'MAT/002',
                'school_id'     => $school->id,
                'faculty_id'    => $faculty->id,
                'department_id' => $department->id,
            ]);

            expect($response->status())->toBe(200);
            expect($student->fresh()->matric_number)->toBe('MAT/002');
            expect($student->fresh()->phone)->toBe('08099999999');
        });

        it('returns 404 for a non-existent student', function () {
            $admin = stdAdmin();
            $setup = stdSetup();
            [$school, $faculty, $department] = $setup;
            $user  = stdStudentUser();

            $response = $this->actingAs($admin)->putJson('/api/admin/students/9999', [
                'user_id'       => $user->id,
                'matric_number' => 'MAT/001',
                'school_id'     => $school->id,
                'faculty_id'    => $faculty->id,
                'department_id' => $department->id,
            ]);

            expect($response->status())->toBe(404);
        });

        it('denies non-admin users', function () {
            $studentUser = stdStudentUser();
            $setup       = stdSetup();
            [$school, $faculty, $department] = $setup;
            $record      = createStudentRecord($studentUser, $setup, 'MAT/001');

            $response = $this->actingAs($studentUser)->putJson("/api/admin/students/{$record->id}", [
                'user_id'       => $studentUser->id,
                'matric_number' => 'MAT/002',
                'school_id'     => $school->id,
                'faculty_id'    => $faculty->id,
                'department_id' => $department->id,
            ]);

            expect($response->status())->toBe(403);
        });
    });

    // ─── Destroy ─────────────────────────────────────────────────────────────────

    describe('Delete Student (DELETE /api/admin/students/{id})', function () {

        it('allows admin to delete a student record', function () {
            $admin   = stdAdmin();
            $setup   = stdSetup();
            $user    = stdStudentUser();
            $student = createStudentRecord($user, $setup, 'MAT/001');

            $response = $this->actingAs($admin)->deleteJson("/api/admin/students/{$student->id}");

            expect($response->status())->toBe(204);
            $this->assertDatabaseMissing('students', ['id' => $student->id]);
        });

        it('returns 404 for a non-existent student', function () {
            $admin = stdAdmin();

            $response = $this->actingAs($admin)->deleteJson('/api/admin/students/9999');

            expect($response->status())->toBe(404);
        });

        it('denies non-admin users', function () {
            $studentUser = stdStudentUser();
            $setup       = stdSetup();
            $record      = createStudentRecord($studentUser, $setup, 'MAT/001');

            $response = $this->actingAs($studentUser)->deleteJson("/api/admin/students/{$record->id}");

            expect($response->status())->toBe(403);
            $this->assertDatabaseHas('students', ['id' => $record->id]);
        });
    });
});
