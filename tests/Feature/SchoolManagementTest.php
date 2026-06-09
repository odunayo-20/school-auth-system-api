<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('School Management', function () {

    // ─── Helpers ────────────────────────────────────────────────────────────────

    function makeAdmin(): User
    {
        return User::create([
            'name'             => 'Admin User',
            'email'            => 'admin@school.test',
            'password'         => Hash::make('password123'),
            'role'             => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    function makeStudent(): User
    {
        return User::create([
            'name'             => 'Student User',
            'email'            => 'student@school.test',
            'password'         => Hash::make('password123'),
            'role'             => 'student',
            'email_verified_at' => now(),
        ]);
    }

    // ─── Index ───────────────────────────────────────────────────────────────────

    describe('List Schools (GET /api/admin/schools)', function () {

        it('allows admin to list all schools', function () {
            $admin = makeAdmin();
            School::create(['name' => 'University of Lagos', 'code' => 'UNILAG']);
            School::create(['name' => 'University of Ibadan', 'code' => 'UI']);

            $response = $this->actingAs($admin)->getJson('/api/admin/schools');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(2);
        });

        it('returns paginated results', function () {
            $admin = makeAdmin();
            foreach (range(1, 15) as $i) {
                School::create(['name' => "School $i", 'code' => "SCH$i"]);
            }

            $response = $this->actingAs($admin)->getJson('/api/admin/schools');

            expect($response->status())->toBe(200);
            expect($response->json('data'))->toHaveCount(10); // default paginate(10)
            expect($response->json('meta.total'))->toBe(15);
        });

        it('denies non-admin users', function () {
            $student = makeStudent();

            $response = $this->actingAs($student)->getJson('/api/admin/schools');

            expect($response->status())->toBe(403);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/admin/schools');

            expect($response->status())->toBe(401);
        });
    });

    // ─── Store ───────────────────────────────────────────────────────────────────

    describe('Create School (POST /api/admin/schools)', function () {

        it('allows admin to create a school', function () {
            $admin = makeAdmin();

            $response = $this->actingAs($admin)->postJson('/api/admin/schools', [
                'name' => 'University of Lagos',
                'code' => 'UNILAG',
            ]);

            expect($response->status())->toBe(201);
            expect($response->json('data.name'))->toBe('University of Lagos');
            $this->assertDatabaseHas('schools', ['name' => 'University of Lagos', 'code' => 'UNILAG']);
        });

        it('validates required fields', function () {
            $admin = makeAdmin();

            $response = $this->actingAs($admin)->postJson('/api/admin/schools', []);

            expect($response->status())->toBe(422);
            expect($response->json('errors'))->toHaveKeys(['name', 'code']);
        });

        it('prevents creating a school with a duplicate code', function () {
            $admin = makeAdmin();
            School::create(['name' => 'First School', 'code' => 'UNILAG']);

            $response = $this->actingAs($admin)->postJson('/api/admin/schools', [
                'name' => 'Another School',
                'code' => 'UNILAG', // duplicate code
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.code'))->not->toBeEmpty();
        });

        it('denies non-admin users', function () {
            $student = makeStudent();

            $response = $this->actingAs($student)->postJson('/api/admin/schools', [
                'name' => 'University of Lagos',
                'code' => 'UNILAG',
            ]);

            expect($response->status())->toBe(403);
        });
    });

    // NOTE: SchoolController does not implement a show() method;
    //       the resource route for GET /schools/{id} is not available.

    // ─── Update ──────────────────────────────────────────────────────────────────

    describe('Update School (PUT /api/admin/schools/{id})', function () {

        it('allows admin to update a school', function () {
            $admin  = makeAdmin();
            $school = School::create(['name' => 'Old Name', 'code' => 'OLD']);

            $response = $this->actingAs($admin)->putJson("/api/admin/schools/{$school->id}", [
                'name' => 'New Name',
                'code' => 'NEW',
            ]);

            expect($response->status())->toBe(200);
            expect($response->json('data.name'))->toBe('New Name');
            expect($school->fresh()->name)->toBe('New Name');
        });

        it('validates required fields on update', function () {
            $admin  = makeAdmin();
            $school = School::create(['name' => 'University of Lagos', 'code' => 'UNILAG']);

            $response = $this->actingAs($admin)->putJson("/api/admin/schools/{$school->id}", []);

            expect($response->status())->toBe(422);
        });

        it('returns 404 for non-existent school', function () {
            $admin = makeAdmin();

            $response = $this->actingAs($admin)->putJson('/api/admin/schools/9999', [
                'name' => 'Test',
                'code' => 'TST',
            ]);

            expect($response->status())->toBe(404);
        });

        it('denies non-admin users', function () {
            $student = makeStudent();
            $school  = School::create(['name' => 'University of Lagos', 'code' => 'UNILAG']);

            $response = $this->actingAs($student)->putJson("/api/admin/schools/{$school->id}", [
                'name' => 'New Name',
                'code' => 'NEW',
            ]);

            expect($response->status())->toBe(403);
        });
    });

    // ─── Destroy ─────────────────────────────────────────────────────────────────

    describe('Delete School (DELETE /api/admin/schools/{id})', function () {

        it('allows admin to delete a school', function () {
            $admin  = makeAdmin();
            $school = School::create(['name' => 'University of Lagos', 'code' => 'UNILAG']);

            $response = $this->actingAs($admin)->deleteJson("/api/admin/schools/{$school->id}");

            expect($response->status())->toBe(204);
            $this->assertDatabaseMissing('schools', ['id' => $school->id]);
        });

        it('returns 404 for non-existent school', function () {
            $admin = makeAdmin();

            $response = $this->actingAs($admin)->deleteJson('/api/admin/schools/9999');

            expect($response->status())->toBe(404);
        });

        it('denies non-admin users', function () {
            $student = makeStudent();
            $school  = School::create(['name' => 'University of Lagos', 'code' => 'UNILAG']);

            $response = $this->actingAs($student)->deleteJson("/api/admin/schools/{$school->id}");

            expect($response->status())->toBe(403);
            $this->assertDatabaseHas('schools', ['id' => $school->id]);
        });
    });
});
