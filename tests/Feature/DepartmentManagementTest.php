<?php

use App\Models\Department;
use App\Models\Faculty;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Department Management', function () {

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    function deptAdmin(): User
    {
        return User::create([
            'name'              => 'Admin User',
            'email'             => 'admin@dept.test',
            'password'          => Hash::make('password123'),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    function deptStudent(): User
    {
        return User::create([
            'name'              => 'Student User',
            'email'             => 'student@dept.test',
            'password'          => Hash::make('password123'),
            'role'              => 'student',
            'email_verified_at' => now(),
        ]);
    }

    function deptSchoolAndFaculty(): array
    {
        $school  = School::create(['name' => 'University of Lagos', 'code' => 'UNILAG']);
        $faculty = Faculty::create(['name' => 'Engineering', 'school_id' => $school->id]);
        return [$school, $faculty];
    }

    // ─── Index ───────────────────────────────────────────────────────────────────

    describe('List Departments (GET /api/admin/departments)', function () {

        it('allows admin to list all departments', function () {
            $admin = deptAdmin();
            [, $faculty] = deptSchoolAndFaculty();
            Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);
            Department::create(['name' => 'Mechanical Engineering', 'faculty_id' => $faculty->id]);

            $response = $this->actingAs($admin)->getJson('/api/admin/departments');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(2);
        });

        it('eager loads the faculty relationship', function () {
            $admin = deptAdmin();
            [, $faculty] = deptSchoolAndFaculty();
            Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);

            $response = $this->actingAs($admin)->getJson('/api/admin/departments');

            expect($response->status())->toBe(200);
            // DepartmentResource uses 'faculty_id' as the nested key name
            expect($response->json('data.0.faculty_id.name'))->toBe('Engineering');
        });

        it('returns paginated results', function () {
            $admin = deptAdmin();
            [, $faculty] = deptSchoolAndFaculty();
            foreach (range(1, 15) as $i) {
                Department::create(['name' => "Department $i", 'faculty_id' => $faculty->id]);
            }

            $response = $this->actingAs($admin)->getJson('/api/admin/departments');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(10);
            expect($response->json('meta.total'))->toBe(15);
        });

        it('denies non-admin users', function () {
            $student = deptStudent();

            $response = $this->actingAs($student)->getJson('/api/admin/departments');

            expect($response->status())->toBe(403);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/admin/departments');

            expect($response->status())->toBe(401);
        });
    });

    // ─── Store ───────────────────────────────────────────────────────────────────

    describe('Create Department (POST /api/admin/departments)', function () {

        it('allows admin to create a department', function () {
            $admin = deptAdmin();
            [, $faculty] = deptSchoolAndFaculty();

            $response = $this->actingAs($admin)->postJson('/api/admin/departments', [
                'name'       => 'Computer Science',
                'faculty_id' => $faculty->id,
            ]);

            expect($response->status())->toBe(201);
            expect($response->json('data.name'))->toBe('Computer Science');
            $this->assertDatabaseHas('departments', [
                'name'       => 'Computer Science',
                'faculty_id' => $faculty->id,
            ]);
        });

        it('validates required fields', function () {
            $admin = deptAdmin();

            $response = $this->actingAs($admin)->postJson('/api/admin/departments', []);

            expect($response->status())->toBe(422);
            expect($response->json('errors'))->toHaveKeys(['name', 'faculty_id']);
        });

        it('rejects an invalid faculty_id', function () {
            $admin = deptAdmin();

            $response = $this->actingAs($admin)->postJson('/api/admin/departments', [
                'name'       => 'Computer Science',
                'faculty_id' => 9999,
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.faculty_id'))->not->toBeEmpty();
        });

        it('denies non-admin users', function () {
            $student = deptStudent();
            [, $faculty] = deptSchoolAndFaculty();

            $response = $this->actingAs($student)->postJson('/api/admin/departments', [
                'name'       => 'Computer Science',
                'faculty_id' => $faculty->id,
            ]);

            expect($response->status())->toBe(403);
        });
    });

    // NOTE: DepartmentController does not implement a show() method;
    //       the resource route for GET /departments/{id} is not available.

    // ─── Update ──────────────────────────────────────────────────────────────────

    describe('Update Department (PUT /api/admin/departments/{id})', function () {

        it('allows admin to update a department', function () {
            $admin = deptAdmin();
            [, $faculty] = deptSchoolAndFaculty();
            $dept = Department::create(['name' => 'Old Name', 'faculty_id' => $faculty->id]);

            $response = $this->actingAs($admin)->putJson("/api/admin/departments/{$dept->id}", [
                'name'       => 'New Name',
                'faculty_id' => $faculty->id,
            ]);

            expect($response->status())->toBe(200);
            expect($response->json('data.name'))->toBe('New Name');
            expect($dept->fresh()->name)->toBe('New Name');
        });

        it('rejects update with invalid faculty_id', function () {
            $admin = deptAdmin();
            [, $faculty] = deptSchoolAndFaculty();
            $dept = Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);

            $response = $this->actingAs($admin)->putJson("/api/admin/departments/{$dept->id}", [
                'name'       => 'Computer Science',
                'faculty_id' => 9999,
            ]);

            expect($response->status())->toBe(422);
        });

        it('returns 404 for non-existent department', function () {
            $admin = deptAdmin();
            [, $faculty] = deptSchoolAndFaculty();

            $response = $this->actingAs($admin)->putJson('/api/admin/departments/9999', [
                'name'       => 'Test',
                'faculty_id' => $faculty->id,
            ]);

            expect($response->status())->toBe(404);
        });

        it('denies non-admin users', function () {
            $student = deptStudent();
            [, $faculty] = deptSchoolAndFaculty();
            $dept = Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);

            $response = $this->actingAs($student)->putJson("/api/admin/departments/{$dept->id}", [
                'name'       => 'New Name',
                'faculty_id' => $faculty->id,
            ]);

            expect($response->status())->toBe(403);
        });
    });

    // ─── Destroy ─────────────────────────────────────────────────────────────────

    describe('Delete Department (DELETE /api/admin/departments/{id})', function () {

        it('allows admin to delete a department', function () {
            $admin = deptAdmin();
            [, $faculty] = deptSchoolAndFaculty();
            $dept = Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);

            $response = $this->actingAs($admin)->deleteJson("/api/admin/departments/{$dept->id}");

            expect($response->status())->toBe(204);
            $this->assertDatabaseMissing('departments', ['id' => $dept->id]);
        });

        it('returns 404 for non-existent department', function () {
            $admin = deptAdmin();

            $response = $this->actingAs($admin)->deleteJson('/api/admin/departments/9999');

            expect($response->status())->toBe(404);
        });

        it('denies non-admin users', function () {
            $student = deptStudent();
            [, $faculty] = deptSchoolAndFaculty();
            $dept = Department::create(['name' => 'Computer Science', 'faculty_id' => $faculty->id]);

            $response = $this->actingAs($student)->deleteJson("/api/admin/departments/{$dept->id}");

            expect($response->status())->toBe(403);
            $this->assertDatabaseHas('departments', ['id' => $dept->id]);
        });
    });
});
