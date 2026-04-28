<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\AdminRoutesController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/leave/view', [LeaveController::class, 'view'])->name('leave.view');
    Route::get('/leave/form', [LeaveController::class, 'form'])->name('leave.form');
    Route::post('/leave/create', [LeaveController::class, 'create'])->name('leave.create');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/leave-requests', [AdminRoutesController::class, 'leaveRequests'])->name('admin.leave-requests');
    Route::get('/admin/users', [AdminRoutesController::class, 'users'])->name('admin.users');



    Route::post('/admin/users/promote/{user}', [UserManagementController::class, 'promote'])->name('admin.users.promote');
    Route::post('/admin/users/demote/{user}', [UserManagementController::class, 'demote'])->name('admin.users.demote');
});

require __DIR__.'/auth.php';
