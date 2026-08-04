<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectChildController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/shares/{token}', [ShareController::class, 'showPublic']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::post('/sync/push', [SyncController::class, 'push']);
        Route::get('/sync/pull', [SyncController::class, 'pull']);

        Route::get('/projects', [ProjectController::class, 'index']);
        Route::post('/projects', [ProjectController::class, 'store']);
        Route::get('/projects/{project}', [ProjectController::class, 'show']);
        Route::put('/projects/{project}', [ProjectController::class, 'update']);
        Route::patch('/projects/{project}', [ProjectController::class, 'update']);
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy']);
        Route::post('/projects/{project}/media', [ProjectController::class, 'uploadMedia']);

        Route::get('/projects/{project}/shares', [ShareController::class, 'index']);
        Route::post('/projects/{project}/shares', [ShareController::class, 'store']);
        Route::delete('/projects/{project}/shares/{share}', [ShareController::class, 'destroy']);
        Route::post('/shares/{token}/accept', [ShareController::class, 'accept']);

        $child = ProjectChildController::class;
        Route::get('/projects/{project}/expenses', [$child, 'expensesIndex']);
        Route::post('/projects/{project}/expenses', [$child, 'expensesStore']);
        Route::get('/projects/{project}/tasks', [$child, 'tasksIndex']);
        Route::post('/projects/{project}/tasks', [$child, 'tasksStore']);
        Route::get('/projects/{project}/inventory', [$child, 'inventoryIndex']);
        Route::post('/projects/{project}/inventory', [$child, 'inventoryStore']);
        Route::get('/projects/{project}/usages', [$child, 'usagesIndex']);
        Route::post('/projects/{project}/usages', [$child, 'usagesStore']);
        Route::get('/projects/{project}/activities', [$child, 'activitiesIndex']);
        Route::post('/projects/{project}/activities', [$child, 'activitiesStore']);
        Route::get('/projects/{project}/milestones', [$child, 'milestonesIndex']);
        Route::post('/projects/{project}/milestones', [$child, 'milestonesStore']);
        Route::post('/projects/{project}/receipts', [$child, 'receiptsStore']);
        Route::delete('/projects/{project}/{entity}/{id}', [$child, 'destroyChild'])
            ->whereIn('entity', ['expenses', 'tasks', 'inventory', 'usages', 'activities', 'milestones', 'receipts']);
    });
});
