<?php

use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectMemberController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ExportController;

// Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user',    [AuthController::class, 'user']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::post('/dashboard/check-overdue', [DashboardController::class, 'checkOverdue']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

    // Tags
    Route::apiResource('tags', TagController::class);

    // Project Members — قبل apiResource
    Route::get('/projects/{project}/members', [ProjectMemberController::class, 'index']);
    Route::post('/projects/{project}/members', [ProjectMemberController::class, 'store']);
    Route::patch('/projects/{project}/members/{member}', [ProjectMemberController::class, 'update']);
    Route::patch('/projects/{project}/members/{member}/accept', [ProjectMemberController::class, 'accept']);
    Route::patch('/projects/{project}/members/{member}/reject', [ProjectMemberController::class, 'reject']);
    Route::delete('/projects/{project}/members/{member}', [ProjectMemberController::class, 'destroy']);

    // Projects
    Route::apiResource('projects', ProjectController::class);

    // Tasks — خاصة (قبل apiResource)
    Route::patch('/tasks/reorder', [TaskController::class, 'reorder']);
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus']);
    Route::get('/tasks/calendar', [TaskController::class, 'calendar']);
    Route::patch('/tasks/{task}/due-date', [TaskController::class, 'updateDueDate']);

    // Comments
    Route::get('/tasks/{task}/comments',  [CommentController::class, 'index']);
    Route::post('/tasks/{task}/comments', [CommentController::class, 'store']);
    Route::delete('/comments/{comment}',  [CommentController::class, 'destroy']);

    // Attachments
    Route::get('/tasks/{task}/attachments',  [AttachmentController::class, 'index']);
    Route::post('/tasks/{task}/attachments', [AttachmentController::class, 'store']);
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy']);

    // داخل middleware auth:sanctum:
    Route::get('/export/tasks/pdf', [ExportController::class, 'tasksPdf']);
    Route::get('/export/tasks/excel', [ExportController::class, 'tasksExcel']);

    // Tasks — CRUD
    Route::apiResource('tasks', TaskController::class);
});