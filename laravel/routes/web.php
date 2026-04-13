<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\LostReportController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\AdminTaskController;
use App\Http\Controllers\SuccessLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'landing'])->name('landing');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ── Resourceful routes (student-facing) ─────────────────────────────
    Route::resource('items', ItemController::class);
    Route::resource('lost-reports', LostReportController::class);

    Route::post('items/{item}/claims', [ClaimController::class, 'store'])->name('claims.store');
    Route::patch('claims/{claim}', [ClaimController::class, 'update'])->name('claims.update');

    // ── Profile ─────────────────────────────────────────────────────────
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ── Messaging ───────────────────────────────────────────────────────
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');

    // ── Chatbot ─────────────────────────────────────────────────────────
    Route::post('/chatbot/ask', [ChatbotController::class, 'ask'])->name('chatbot.ask');

    // ── Contact owner (lost reports) ────────────────────────────────────
    Route::post('/lost-reports/{lostReport}/contact', [LostReportController::class, 'contactOwner'])->name('lost-reports.contact');

    // ═══════════════════════════════════════════════════════════════════
    // ADMIN / ORGANIZER / GUARD routes
    // ═══════════════════════════════════════════════════════════════════
    Route::middleware('role:admin,organizer,guard')->group(function () {
        // Manage Found Items
        Route::get('/manage/items', [ItemController::class, 'manage'])->name('items.manage');
        Route::patch('/items/{item}/status', [ItemController::class, 'updateStatus'])->name('items.updateStatus');
        Route::post('/items/{item}/status', [ItemController::class, 'updateStatus']);  // POST alias for AJAX
        Route::patch('/items/{item}/storage', [ItemController::class, 'updateStorage'])->name('items.updateStorage');

        // Manage Lost Reports
        Route::get('/manage/lost-reports', [LostReportController::class, 'manage'])->name('lost-reports.manage');
        Route::patch('/lost-reports/{lostReport}/status', [LostReportController::class, 'updateStatus'])->name('lost-reports.updateStatus');
        Route::post('/lost-reports/{lostReport}/status', [LostReportController::class, 'updateStatus']); // POST alias

        // Claim actions
        Route::post('/claims/{claim}/verify', [ClaimController::class, 'verify'])->name('claims.verify');
        Route::post('/claims/{claim}/reject', [ClaimController::class, 'reject'])->name('claims.reject');
        Route::post('/claims/{claim}/return', [ClaimController::class, 'confirmReturn'])->name('claims.return');

        // Task status update (from organizer dashboard)
        Route::post('/admin/tasks/{task}/status', [AdminTaskController::class, 'update'])->name('tasks.updateStatus');

        // Success Log
        Route::get('/success-log', [SuccessLogController::class, 'index'])->name('success-log');
        Route::get('/claim-slip/{item}', [SuccessLogController::class, 'claimSlip'])->name('claim-slip');
    });

    // ═══════════════════════════════════════════════════════════════════
    // ADMIN / SUPERADMIN only
    // ═══════════════════════════════════════════════════════════════════
    Route::middleware('role:admin,superadmin')->group(function () {
        // Manage Users
        Route::get('/manage/users', [ManageUserController::class, 'index'])->name('admin.users.index');
        Route::post('/manage/users', [ManageUserController::class, 'store'])->name('admin.users.store');
        Route::put('/manage/users/{user}', [ManageUserController::class, 'update'])->name('admin.users.update');
        Route::delete('/manage/users/{user}', [ManageUserController::class, 'destroy'])->name('admin.users.destroy');
        Route::post('/manage/users/{user}/reset-password', [ManageUserController::class, 'resetPassword'])->name('admin.users.resetPassword');

        // Task Management
        Route::resource('tasks', AdminTaskController::class)->except(['create', 'show', 'edit']);
    });
});

require __DIR__ . '/auth.php';
