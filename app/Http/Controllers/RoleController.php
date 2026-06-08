<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    /**
     * Get available roles
     */
    public function getAvailableRoles()
    {
        return response()->json([
            'roles' => ['admin', 'student'],
        ]);
    }

    /**
     * Update user role (Admin only)
     */
    public function updateUserRole(Request $request)
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            throw ValidationException::withMessages([
                'authorization' => 'Only admins can update user roles.',
            ]);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:admin,student',
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Prevent updating your own role to avoid locking yourself out
        if (auth()->id() === $user->id) {
            throw ValidationException::withMessages([
                'user_id' => 'You cannot change your own role.',
            ]);
        }

        $oldRole = $user->role;
        $user->update(['role' => $validated['role']]);

        return response()->json([
            'message' => "User role updated from '{$oldRole}' to '{$validated['role']}'.",
            'user' => UserResource::make($user),
        ]);
    }

    /**
     * Update own role (self service for specific transitions)
     */
    public function updateOwnRole(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'role' => 'required|in:student',
        ]);

        // Students and lecturers can update their own roles
        if (!in_array($user->role, ['student', 'lecturer'])) {
            throw ValidationException::withMessages([
                'role' => 'Your current role cannot be changed. Contact an admin.',
            ]);
        }

        $oldRole = $user->role;
        $user->update(['role' => $validated['role']]);

        return response()->json([
            'message' => "Your role has been updated from '{$oldRole}' to '{$validated['role']}'.",
            'user' => UserResource::make($user),
        ]);
    }

    /**
     * Get user's current role
     */
    public function getMyRole(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'role_permissions' => $this->getRolePermissions($user->role),
        ]);
    }

    /**
     * Get permissions for a specific role
     */
    private function getRolePermissions(string $role): array
    {
        $permissions = [
            'admin' => [
                'manage_users',
                'manage_schools',
                'manage_faculties',
                'manage_departments',
                'manage_students',
                'view_reports',
            ],
            'student' => [
                'view_profile',
                'update_profile',
                'view_courses',
                'submit_assignments',
            ],

        ];

        return $permissions[$role] ?? [];
    }
}
