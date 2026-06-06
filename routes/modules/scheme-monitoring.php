<?php

use App\Modules\SchemeMonitoring\Http\Controllers\Admin\DesignationController;
use App\Modules\SchemeMonitoring\Http\Controllers\Admin\ModuleAccessController;
use App\Modules\SchemeMonitoring\Http\Controllers\Admin\MonitorHierarchyController;
use App\Modules\SchemeMonitoring\Http\Controllers\AttachmentController;
use App\Modules\SchemeMonitoring\Http\Controllers\DashboardController;
use App\Modules\SchemeMonitoring\Http\Controllers\SchemeController;
use App\Modules\SchemeMonitoring\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('monitoring')->name('monitoring.')->group(function (): void {
    Route::get('/', [DashboardController::class, 'show'])->name('dashboard');

    Route::resource('schemes', SchemeController::class)
        ->parameters(['schemes' => 'scheme']);

    Route::resource('tasks', TaskController::class)
        ->parameters(['tasks' => 'task'])
        ->except(['show']);

    // Quick status-flip endpoint used from the dashboard rows.
    Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])
        ->name('tasks.status');

    // Attachments — separate POST endpoints per parent type, single DELETE
    // route on the attachment itself (it knows its own parent).
    Route::post('schemes/{scheme}/attachments', [AttachmentController::class, 'storeForScheme'])
        ->name('schemes.attachments.store');
    Route::post('tasks/{task}/attachments', [AttachmentController::class, 'storeForTask'])
        ->name('tasks.attachments.store');
    Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])
        ->name('attachments.destroy');
});

// Admin-only hierarchy + designation management. Sits under /admin/monitoring/
// alongside the other admin areas.
Route::middleware(['auth'])->prefix('admin/monitoring')->name('admin.monitoring.')->group(function (): void {
    Route::resource('designations', DesignationController::class)
        ->parameters(['designations' => 'designation'])
        ->except(['show']);

    Route::get('hierarchy', [MonitorHierarchyController::class, 'index'])->name('hierarchy.index');
    Route::post('hierarchy/{user}', [MonitorHierarchyController::class, 'update'])->name('hierarchy.update');
    Route::delete('hierarchy/{user}', [MonitorHierarchyController::class, 'destroy'])->name('hierarchy.destroy');

    Route::get('access', [ModuleAccessController::class, 'index'])->name('access.index');
    Route::post('access/{user}', [ModuleAccessController::class, 'grant'])->name('access.grant');
    Route::delete('access/{user}', [ModuleAccessController::class, 'revoke'])->name('access.revoke');
});
