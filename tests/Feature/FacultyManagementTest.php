<?php

use App\Models\Faculty;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Faculty Management', function () {

    // ─── Helpers ────────────────────────────────────────────────────────────────

    function adminUser(): User
    {
        return User::create([
            'name'              => 'Admin User',
            'email'             => 'admin@faculty.test',
            'password'          => Hash::make('password123'),
            'role'              => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    function studentUser(): User
    {
        return User::create([
            'name'              => 'Student User',
            'email'             => 'student@faculty.test',
            'password'          => Hash::make('password123'),
            'role'              => 'student',
            'email_verified_at' => now(),
        ]);
    }

    function createSchool(string $name = 'University of Lagos', string $code = 'UNILAG'): School
    {
        return School::create(['name' => $name, 'code' => $code]);
    }

    // ─── Index ───────────────────────────────────────────────────────────────────

    describe('List Faculties (GET /api/admin/faculties)', function () {

        it('allows admin to list all faculties', function () {
            $admin  = adminUser();
            $school = createSchool();
            Faculty::create(['name' => 'Engineering', 'school_id' => $school->id]);
            Faculty::create(['name' => 'Science', 'school_id' => $school->id]);

            $response = $this->actingAs($admin)->getJson('/api/admin/faculties');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(2);
        });

        it('returns paginated results', function () {
            $admin  = adminUser();
            $school = createSchool();
            foreach (range(1, 15) as $i) {
                Faculty::create(['name' => "Faculty $i", 'school_id' => $school->id]);
            }

            $response = $this->actingAs($admin)->getJson('/api/admin/faculties');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(10);
            expect($response->json('meta.total'))->toBe(15);
        });

        it('denies non-admin users', function () {
            $student = studentUser();

            $response = $this->actingAs($student)->getJson('/api/admin/faculties');

            expect($response->status())->toBe(403);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/admin/faculties');

            expect($response->status())->toBe(401);
        });
    });

    // ─── Store ───────────────────────────────────────────────────────────────────

    describe('Create Faculty (POST /api/admin/faculties)', function () {

        it('allows admin to create a faculty', function () {
            $admin  = adminUser();
            $school = createSchool();

            $response = $this->actingAs($admin)->postJson('/api/admin/faculties', [
                'name'      => 'Engineering',
                'school_id' => $school->id,
            ]);

            expect($response->status())->toBe(201);
            expect($response->json('data.name'))->toBe('Engineering');
            $this->assertDatabaseHas('faculties', ['name' => 'Engineering', 'school_id' => $school->id]);
        });

        it('validates required fields', function () {
            $admin = adminUser();

            $response = $this->actingAs($admin)->postJson('/api/admin/faculties', []);

            expect($response->status())->toBe(422);
            expect($response->json('errors'))->toHaveKeys(['name', 'school_id']);
        });

        it('rejects invalid school_id', function () {
            $admin = adminUser();

            $response = $this->actingAs($admin)->postJson('/api/admin/faculties', [
                'name'      => 'Engineering',
                'school_id' => 9999,
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.school_id'))->not->toBeEmpty();
        });

        it('denies non-admin users', function () {
            $student = studentUser();
            $school  = createSchool();

            $response = $this->actingAs($student)->postJson('/api/admin/faculties', [
                'name'      => 'Engineering',
                'school_id' => $school->id,
            ]);

            expect($response->status())->toBe(403);
        });
    });

    // NOTE: FacultyController does not implement a show() method;
    //       the resource route for GET /faculties/{id} is not available.

    // ─── Update ──────────────────────────────────────────────────────────────────

    describe('Update Faculty (PUT /api/admin/faculties/{id})', function () {

        it('allows admin to update a faculty', function () {
            $admin   = adminUser();
            $school  = createSchool();
            $faculty = Faculty::create(['name' => 'Old Name', 'school_id' => $school->id]);

            $response = $this->actingAs($admin)->putJson("/api/admin/faculties/{$faculty->id}", [
                'name'      => 'New Name',
                'school_id' => $school->id,
            ]);

            expect($response->status())->toBe(200);
            expect($response->json('data.name'))->toBe('New Name');
            expect($faculty->fresh()->name)->toBe('New Name');
        });

        it('rejects update with invalid school_id', function () {
            $admin   = adminUser();
            $school  = createSchool();
            $faculty = Faculty::create(['name' => 'Engineering', 'school_id' => $school->id]);

            $response = $this->actingAs($admin)->putJson("/api/admin/faculties/{$faculty->id}", [
                'name'      => 'Engineering',
                'school_id' => 9999,
            ]);

            expect($response->status())->toBe(422);
        });

        it('returns 404 for non-existent faculty', function () {
            $admin  = adminUser();
            $school = createSchool();

            $response = $this->actingAs($admin)->putJson('/api/admin/faculties/9999', [
                'name'      => 'Test',
                'school_id' => $school->id,
            ]);

            expect($response->status())->toBe(404);
        });

        it('denies non-admin users', function () {
            $student = studentUser();
            $school  = createSchool();
            $faculty = Faculty::create(['name' => 'Engineering', 'school_id' => $school->id]);

            $response = $this->actingAs($student)->putJson("/api/admin/faculties/{$faculty->id}", [
                'name'      => 'New Name',
                'school_id' => $school->id,
            ]);

            expect($response->status())->toBe(403);
        });
    });

    // ─── Destroy ─────────────────────────────────────────────────────────────────

    describe('Delete Faculty (DELETE /api/admin/faculties/{id})', function () {

        it('allows admin to delete a faculty', function () {
            $admin   = adminUser();
            $school  = createSchool();
            $faculty = Faculty::create(['name' => 'Engineering', 'school_id' => $school->id]);

            $response = $this->actingAs($admin)->deleteJson("/api/admin/faculties/{$faculty->id}");

            expect($response->status())->toBe(204);
            $this->assertDatabaseMissing('faculties', ['id' => $faculty->id]);
        });

        it('returns 404 for non-existent faculty', function () {
            $admin = adminUser();

            $response = $this->actingAs($admin)->deleteJson('/api/admin/faculties/9999');

            expect($response->status())->toBe(404);
        });

        it('denies non-admin users', function () {
            $student = studentUser();
            $school  = createSchool();
            $faculty = Faculty::create(['name' => 'Engineering', 'school_id' => $school->id]);

            $response = $this->actingAs($student)->deleteJson("/api/admin/faculties/{$faculty->id}");

            expect($response->status())->toBe(403);
            $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
        });
    });
});
