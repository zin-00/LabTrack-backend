<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\computers\ComputerController;
use App\Http\Controllers\computers\ComputerLogController;
use App\Http\Controllers\laboratories\LabController;
use App\Http\Controllers\program\ProgramController;
use App\Http\Controllers\students\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;






// Authenticated user info (optional)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('auth/user', [AuthController::class, 'user'])->name('auth.user');
    Route::delete('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

    // Computer routes
    Route::get('/computers', [ComputerController::class, 'index']);
    Route::post('/computers', [ComputerController::class, 'store']);
    Route::put('/computers/update/{id}', [ComputerController::class, 'update']);
    Route::delete('/computers/{id}', [ComputerController::class, 'destroy']);

    // Laboratory routes
    Route::get('/laboratories', [LabController::class, 'index']);

    // Program routes
    Route::get('/programs', [ProgramController::class, 'index']);

    // Computer logs
    Route::get('/logs', [ComputerLogController::class, 'index']);

    // State

    Route::post('/pc-online', [ComputerController::class, 'isOnline']);
    Route::put('/computer/state/{id}', [ComputerController::class, 'computerState']);



    // Students
    Route::post('/students', [StudentController::class, 'store']);
    Route::put('/students/{id}', [StudentController::class, 'update']);
    Route::get('/students', [StudentController::class, 'index']);
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);


});

    Route::get('/computer/status/{ip}', [ComputerController::class, 'getStatus']);
    Route::post('/pc-offline/{ip}', [ComputerController::class, 'isOffline']);
