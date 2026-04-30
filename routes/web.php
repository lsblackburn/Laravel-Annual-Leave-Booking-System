<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\AdminRoutesController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/leave-requests/calendar-events', [LeaveController::class, 'calendar_events'])->name('leave-requests.calendar-events');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {

    Route::get('/calendar', function () {
        return view('calendar');
    })->name('calendar');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/leave/view', [LeaveController::class, 'view'])->name('leave.view');
    Route::get('/leave/form', [LeaveController::class, 'form'])->name('leave.form');
    Route::post('/leave/create', [LeaveController::class, 'create'])->name('leave.create');
});

Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/leave-requests', [AdminRoutesController::class, 'leave_requests'])->name('admin.leave-requests');
    Route::get('/admin/users', [AdminRoutesController::class, 'users'])->name('admin.users');
    Route::get('/admin/users/edit/{user}', [AdminRoutesController::class, 'edit_user'])->name('admin.users.edit');
    Route::get('/admin/users/create', [AdminRoutesController::class, 'register_user'])->name('admin.users.create');
    Route::post('/admin/users/register', [RegisteredUserController::class, 'store'])->name('admin.users.register');
    Route::patch('/admin/users/update/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
    Route::put('/admin/users/password/{user}', [PasswordController::class, 'update'])->name('admin.users.password.update');
    Route::delete('/admin/users/delete/{user}', [ProfileController::class, 'destroy'])->name('admin.users.delete');
    Route::post('/admin/users/promote/{user}', [UserManagementController::class, 'promote'])->name('admin.users.promote');
    Route::post('/admin/users/demote/{user}', [UserManagementController::class, 'demote'])->name('admin.users.demote');

    Route::post('/admin/leave-requests/response/{request}', [LeaveController::class, 'leave_response'])->name('admin.leave-requests.response');
    
});

require __DIR__.'/auth.php';
