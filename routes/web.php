<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\AdminRoutesController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserDepartmentController;
use App\Http\Controllers\NonWorkDayController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

// 2FA challenge during login (user not yet authenticated)
Route::get('/2fa/verify', [TwoFactorController::class, 'showVerify'])->name('2fa.verify');
Route::post('/2fa', [TwoFactorController::class, 'verify'])->name('2fa');

Route::middleware(['auth', '2fa.remember'])->group(function () {

    Route::get('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::get('/2fa/disable', [TwoFactorController::class, 'showDisableForm'])->name('2fa.disable.form');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])->name('2fa.enable');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware(['verified'])->name('dashboard');

    Route::get('/calendar', function () {
        return view('calendar');
    })->name('calendar');

    Route::get('/leave-requests/calendar-events', [LeaveController::class, 'calendarEvents'])->name('leave-requests.calendar-events');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/leave/view', [LeaveController::class, 'view'])->name('leave.view');
    Route::get('/leave/edit/{request}', [LeaveController::class, 'edit'])->name('leave.edit');
    Route::put('/leave/update/{leaveRequest}', [LeaveController::class, 'update'])->name('leave.update');
    Route::get('/leave/form', [LeaveController::class, 'form'])->name('leave.form');
    Route::post('/leave/create', [LeaveController::class, 'create'])->name('leave.create');
    Route::delete('/leave/delete/{leave}', [LeaveController::class, 'delete'])->name('leave.delete');
    Route::patch('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
});

Route::middleware(['auth', '2fa.remember', 'role:admin'])->group(function () {
    Route::get('/admin/leave-requests', [AdminRoutesController::class, 'leaveRequests'])->name('admin.leave-requests');
    Route::get('/admin/users', [AdminRoutesController::class, 'users'])->name('admin.users');
    Route::get('/admin/users/edit/{user}', [AdminRoutesController::class, 'editUser'])->name('admin.users.edit');
    Route::get('/admin/users/create', [AdminRoutesController::class, 'registerUser'])->name('admin.users.create');
    Route::post('/admin/users/register', [RegisteredUserController::class, 'store'])->name('admin.users.register');
    Route::patch('/admin/users/update/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
    Route::put('/admin/users/password/{user}', [PasswordController::class, 'update'])->name('admin.users.password.update');
    Route::delete('/admin/users/delete/{user}', [ProfileController::class, 'destroy'])->name('admin.users.delete');
    Route::post('/admin/users/promote/{user}', [UserManagementController::class, 'promote'])->name('admin.users.promote');
    Route::post('/admin/users/demote/{user}', [UserManagementController::class, 'demote'])->name('admin.users.demote');
    Route::post('/admin/leave-requests/response/{request}', [LeaveController::class, 'leaveResponse'])->name('admin.leave-requests.response');
    
    Route::get('/admin/app-configuration', [AdminRoutesController::class, 'viewConfig'])->name('admin.view-config');
    Route::get('/admin/app-configuration/leave-rules', [AdminRoutesController::class, 'viewLeaveRules'])->name('admin.view-leave-rules');
    Route::patch('/admin/app-configuration/work-days/update', [LeaveController::class, 'updateWorkDays'])->name('admin.work-days.update');
    Route::post('/admin/app-configuration/non-work-days/create', [NonWorkDayController::class, 'store'])->name('admin.non-work-days.create');
    Route::delete('/admin/app-configuration/non-work-days/delete/{nonWorkDay}', [NonWorkDayController::class, 'destroy'])->name('admin.non-work-days.delete');
    Route::patch('/admin/app-configuration/leave-refresh/update', [LeaveController::class, 'updateLeaveRefresh'])->name('admin.leave-refresh.update');
    Route::patch('/admin/app-configuration/leave-allowance/update', [LeaveController::class, 'updateLeaveAllowance'])->name('admin.leave-allowance.update');

    Route::get('/admin/app-configuration/company-departments', [AdminRoutesController::class, 'viewCompanyDepartments'])->name('admin.view-company-departments');
    Route::post('/admin/app-configuration/company-departments/create', [UserDepartmentController::class, 'createCompanyDepartments'])->name('admin.company-departments.create');
    Route::delete('/admin/app-configuration/company-departments/delete/{department}', [UserDepartmentController::class, 'deleteCompanyDepartment'])->name('admin.company-departments.delete');
});

require __DIR__.'/auth.php';
