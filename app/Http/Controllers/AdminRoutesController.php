<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Leave;
use App\Models\User;
use App\Models\LeaveSetting;
use App\Models\UserDepartment;
use App\Models\WorkDays;

class AdminRoutesController extends Controller
{

    public function leave_requests()
    {
        $leaveRequests = Leave::select('leaves.*', 'users.name as user_name')
            ->join('users', 'leaves.user_id', '=', 'users.id')
            ->where('leaves.status', 'pending')
            ->paginate(30);
        // This query retrieves all pending leave requests along with the name of the user who made each request

        return view('admin.leave-requests', compact('leaveRequests'));
    }

    public function users()
    {
        $users = User::orderBy('role', 'asc')->orderBy('name', 'asc')->paginate(30);
        // Order users by role first, then by name alphabetically

        return view('admin.users', compact('users'));
    }


    public function edit_user(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users')->with('error', 'You cannot edit yourself in the Admin panel.');
        }

        $user = User::findOrFail($user->id);

        $departments = UserDepartment::orderBy('department', 'asc')->get();

        return view('admin.edit-user', compact('user', 'departments'));
    }

    public function register_user()
    {
        $departments = UserDepartment::orderBy('department', 'asc')->get();

        return view('auth.register', [
            'suggestedColour' => User::generateUniqueColour(),
            'departments' => $departments,
        ]);
    }

    public function view_config()
    {
        return view('admin.app-config');
    }

    public function view_leave_rules() {
        $settings = LeaveSetting::first();
        $workDays = WorkDays::query()
            ->orderByRaw("CASE day
                WHEN 'Monday' THEN 1
                WHEN 'Tuesday' THEN 2
                WHEN 'Wednesday' THEN 3
                WHEN 'Thursday' THEN 4
                WHEN 'Friday' THEN 5
                WHEN 'Saturday' THEN 6
                WHEN 'Sunday' THEN 7
                ELSE 8
            END")
            ->get();
        $selectedWorkDayIds = $workDays
            ->where('active', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return view('admin.config.leave-rules', compact('settings', 'workDays', 'selectedWorkDayIds'));
    }

    public function view_company_departments() {
        $departments = UserDepartment::orderBy('department', 'asc')->paginate(30);

        return view('admin.config.company-departments', compact('departments'));
    }
}
