<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Api\BroadcastAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\computers\ComputerController;
use App\Http\Controllers\computers\ComputerLogController;
use App\Http\Controllers\computers\ComputerStatusDistribution;
use App\Http\Controllers\laboratories\LabController;
use App\Http\Controllers\program\ProgramController;
use App\Http\Controllers\RequestAccess\RequestAccessController;
use App\Http\Controllers\students\StudentController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;




Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('auth/check-email', [AuthController::class, 'isEmailExist']);

Route::post('/computer-unlock', [ComputerController::class, 'unlockAssignedComputer']);



Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/user', [AuthController::class, 'user'])->name('auth.user');
    Route::delete('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

        // Request Access
    Route::get('/request-access', [RequestAccessController::class, 'index']);
    Route::patch('/request-access/{id}/approve', [RequestAccessController::class, 'approve']);
    Route::patch('/request-access/{id}/reject', [RequestAccessController::class, 'reject']);


    // Computer routes
    Route::get('/computers', [ComputerController::class, 'index']);
    Route::post('/computers', [ComputerController::class, 'store']);
    Route::put('/computers/update/{id}', [ComputerController::class, 'update']);
    Route::delete('/computers/{id}', [ComputerController::class, 'destroy']);
    Route::get('/computers/null-lab', [ComputerController::class, 'showAllComputerWithNullLabId']);

    // Assigned computer to students
     // Single assignment
    Route::post('/computer/assign', [ComputerController::class, 'assignStudent']);

    // Bulk assignment
    Route::post('/computer/bulk-assign', [ComputerController::class, 'bulkAssign']);

    // Unassign students
    Route::post('/computer/unassign', [ComputerController::class, 'unassignStudent']);

    // Get unassigned students with filters
    Route::get('/students/unassigned', [ComputerController::class, 'getUnassignedStudents']);

    // Get computer assignments
    Route::get('/computer/{id}/assignments', [ComputerController::class, 'getComputerAssignments']);

    // Laboratory routes
    Route::get('/laboratories', [LabController::class, 'index']);
    Route::post('/laboratories', [LabController::class, 'store']);
    Route::put('/laboratories/{id}', [LabController::class, 'update']);
    Route::delete('/laboratories/{id}', [LabController::class, 'destroy']);
    Route::post('/assign-laboratories', [ComputerController::class, 'assignLaboratory']);


    // Program routes
    Route::get('/programs', [ProgramController::class, 'index']);

    // Computer logs
    Route::get('/logs', [ComputerLogController::class, 'index']);

    // Route::post('/pc-online/{ip}', [ComputerController::class, 'isOnline']);
    Route::put('/computer/state/{id}', [ComputerController::class, 'unlock']);

    // Students
    Route::post('/students', [StudentController::class, 'store']);
    Route::put('/students/{id}', [StudentController::class, 'update']);
    Route::get('/students', [StudentController::class, 'index']);
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);
    Route::post('/students/import', [StudentController::class, 'importStudents']);

    // Admin routes
    Route::prefix('admin')->group(function () {
        Route::get('/users', [AdminController::class, 'index']);
        Route::post('/users', [AdminController::class, 'store']);
        Route::put('/users/{id}', [AdminController::class, 'update']);
        Route::get('/users/{id}', [AdminController::class, 'edit']);
        Route::delete('/users/{id}', [AdminController::class, 'delete']);
    });

    // Students assign computers routes
    Route::get('/students/unassigned', [StudentController::class, 'getUnassignedStudents']);
    Route::post('/computer/assign', [ComputerController::class, 'assignStudent']);

    // Computer status distribution
    Route::get('/status-distribution', [ComputerStatusDistribution::class, 'index']);
    Route::get('/data-distribution', [ComputerStatusDistribution::class, 'getDataDistribution']);

    // Export
    Route::get('/logs/export', [ComputerLogController::class, 'export']);

});

    Route::get('/computer/status/{ip}', [ComputerController::class, 'getStatus']);
    Route::post('/pc-offline/{ip}', [ComputerController::class, 'isOffline']);
    Route::post('/computer/register', [ComputerController::class, 'register_computer']);
    Route::post('/pc-online/{ip}', [ComputerController::class, 'isOnline']);

    // Request access
    Route::post('/request-access', [RequestAccessController::class, 'store']);


Route::get('/data-distribution-test', function() {
    return response()->json(['test' => 'ok']);
});
Route::get('/test-unlock/{ip}', function($ip) {
    $computer = \App\Models\Computer::where('ip_address', $ip)->first();
    if ($computer) {
        \App\Events\ComputerUnlockRequested::dispatch($computer);
        return response()->json(['message' => 'Event dispatched']);
    }
    return response()->json(['message' => 'Computer not found'], 404);
});
