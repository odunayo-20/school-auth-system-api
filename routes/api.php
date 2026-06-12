<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\ConfirmStudentController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\VerifyStudentController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\StudentController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-email', [EmailVerificationController::class, 'verifyEmail']);
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/request-signin-code', [AuthController::class, 'requestSignInCode']);
    Route::post('/signin-with-code', [AuthController::class, 'signInWithCode']);
    Route::post('/confirm-student', [ConfirmStudentController::class, 'confirmStudent']);
    Route::post('/verify-identity', [VerifyStudentController::class, 'verifyIdentity']);
});

// Public role routes
Route::prefix('roles')->group(function () {
    Route::get('/available', [RoleController::class, 'getAvailableRoles']);
});



// Authenticated user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('roles')->group(function () {
        Route::get('/my-role', [RoleController::class, 'getMyRole']);
        Route::put('/my-role', [RoleController::class, 'updateOwnRole']);
    });
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [LoginController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

    Route::prefix('admin')->group(function () {
        // Admin routes here (e.g. user management)
        Route::resource('students', StudentController::class);
        Route::resource('schools', SchoolController::class);
        Route::resource('faculties', FacultyController::class);
        Route::resource('departments', DepartmentController::class);

        // Role management for admins
        Route::put('/roles/{user_id}', [RoleController::class, 'updateUserRole']);
    });
});

Route::middleware(['auth:sanctum', 'role:student'])->group(function () {
    Route::prefix('student')->group(function () {
        // Student routes here
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// require __DIR__.'/auth.php';
