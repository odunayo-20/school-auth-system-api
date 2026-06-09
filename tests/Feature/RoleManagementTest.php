<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Role Management', function () {

    describe('Get Available Roles', function () {
        it('returns available roles', function () {
            $response = $this->getJson('/api/roles/available');

            expect($response->status())->toBe(200);
            expect($response->json('roles'))->toBeArray();
            expect($response->json('roles'))->toContain('admin', 'student');
        });
    });

    describe('Get My Role', function () {
        it('returns authenticated user role', function () {
            $user = User::create([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]);

            $response = $this->actingAs($user)->getJson('/api/roles/my-role');

            expect($response->status())->toBe(200);
            expect($response->json('role'))->toBe('student');
            expect($response->json('user_id'))->toBe($user->id);
            expect($response->json('email'))->toBe('john@example.com');
            expect($response->json('role_permissions'))->toBeArray();
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/roles/my-role');

            expect($response->status())->toBe(401);
        });

        it('returns role-specific permissions', function () {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]);

            $response = $this->actingAs($admin)->getJson('/api/roles/my-role');

            expect($response->status())->toBe(200);
            expect($response->json('role_permissions'))->toContain(
                'manage_users',
                'manage_schools',
                'manage_faculties',
                'manage_departments'
            );
        });
    });

    describe('Update Own Role', function () {
        it('allows non-admin users to set their role back to student (self-service)', function () {
            // updateOwnRole only allows 'in:student' — i.e. the user can reset their role
            $user = User::create([
                'name'     => 'John Doe',
                'email'    => 'john@example.com',
                'password' => Hash::make('password123'),
                'role'     => 'student',
            ]);

            $response = $this->actingAs($user)->putJson('/api/roles/my-role', [
                'role' => 'student',
            ]);

            expect($response->status())->toBe(200);
            expect($response->json('message'))->toContain('student');
            expect($user->fresh()->role)->toBe('student');
        });

        it('requires authentication', function () {
            $response = $this->putJson('/api/roles/my-role', [
                'role' => 'lecturer',
            ]);

            expect($response->status())->toBe(401);
        });

        it('validates role value — only "student" is accepted for self-update', function () {
            $user = User::create([
                'name'     => 'John Doe',
                'email'    => 'john@example.com',
                'password' => Hash::make('password123'),
                'role'     => 'student',
            ]);

            $response = $this->actingAs($user)->putJson('/api/roles/my-role', [
                'role' => 'invalid_role',
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.role'))->not->toBeEmpty();
        });

        it('prevents admins from changing their own role', function () {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]);

            $response = $this->actingAs($admin)->putJson('/api/roles/my-role', [
                'role' => 'student',
            ]);

            expect($response->status())->toBe(422);
            expect($admin->fresh()->role)->toBe('admin');
        });
    });

    describe('Update User Role (Admin Only)', function () {
        it('allows admin to update other user roles (to admin or student)', function () {
            $admin = User::create([
                'name'     => 'Admin User',
                'email'    => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
            ]);

            $student = User::create([
                'name'     => 'Student User',
                'email'    => 'student@example.com',
                'password' => Hash::make('password123'),
                'role'     => 'student',
            ]);

            // updateUserRole only allows 'admin' or 'student'
            $response = $this->actingAs($admin)->putJson('/api/admin/roles/' . $student->id, [
                'user_id' => $student->id,
                'role'    => 'admin',
            ]);

            expect($response->status())->toBe(200);
            expect($student->fresh()->role)->toBe('admin');
        });

        it('requires admin authorization', function () {
            $student = User::create([
                'name' => 'Student User',
                'email' => 'student@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]);

            $otherStudent = User::create([
                'name' => 'Other Student',
                'email' => 'other@example.com',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]);

            $response = $this->actingAs($student)->putJson('/api/admin/roles/' . $otherStudent->id, [
                'role' => 'admin',
            ]);

            expect($response->status())->toBe(403);
        });

        it('prevents admin from changing their own role', function () {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]);

            $response = $this->actingAs($admin)->putJson('/api/admin/roles/' . $admin->id, [
                'role' => 'student',
            ]);

            expect($response->status())->toBe(422);
            expect($admin->fresh()->role)->toBe('admin');
        });

        it('validates role value — only admin/student are accepted', function () {
            $admin = User::create([
                'name'     => 'Admin User',
                'email'    => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
            ]);

            $student = User::create([
                'name'     => 'Student User',
                'email'    => 'student@example.com',
                'password' => Hash::make('password123'),
                'role'     => 'student',
            ]);

            $response = $this->actingAs($admin)->putJson('/api/admin/roles/' . $student->id, [
                'user_id' => $student->id,
                'role'    => 'invalid_role',
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.role'))->not->toBeEmpty();
        });

        it('validates user existence', function () {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]);

            $response = $this->actingAs($admin)->putJson('/api/admin/roles/9999', [
                'role' => 'student',
            ]);

            expect($response->status())->toBe(422);
            expect($response->json('errors.user_id'))->not->toBeEmpty();
        });
    });
});
