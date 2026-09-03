<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProjectExportController;
use App\Http\Controllers\Web\ProjectPageController;
use App\Http\Controllers\Web\SharePageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::get('/p/{token}', [SharePageController::class, 'show'])->name('share.show');
Route::post('/p/{token}/accept', [SharePageController::class, 'accept'])
    ->middleware('auth')
    ->name('share.accept');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/projects/{project}', [ProjectPageController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/expenses', [ProjectPageController::class, 'expenses'])->name('projects.expenses');
    Route::get('/projects/{project}/tasks', [ProjectPageController::class, 'tasks'])->name('projects.tasks');
    Route::get('/projects/{project}/inventory', [ProjectPageController::class, 'inventory'])->name('projects.inventory');
    Route::get('/projects/{project}/activities', [ProjectPageController::class, 'activities'])->name('projects.activities');
    Route::get('/projects/{project}/schedule', [ProjectPageController::class, 'schedule'])->name('projects.schedule');
    Route::get('/projects/{project}/reports', [ProjectPageController::class, 'reports'])->name('projects.reports');
    Route::get('/projects/{project}/export/pdf', [ProjectExportController::class, 'pdf'])->name('projects.export.pdf');
    Route::get('/projects/{project}/export/csv/{kind?}', [ProjectExportController::class, 'csv'])
        ->whereIn('kind', ['all', 'expenses', 'tasks', 'inventory', 'activities', 'schedule'])
        ->name('projects.export.csv');
});
