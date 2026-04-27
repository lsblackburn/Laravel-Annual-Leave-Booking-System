<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Leave;
use App\Models\User;

class AdminRoutesController extends Controller
{

    public function leaveRequests()
    {
        $leaveRequests = Leave::select('leaves.*', 'users.name as user_name')
            ->join('users', 'leaves.user_id', '=', 'users.id')
            ->where('leaves.status', 'pending')
            ->get();
        // This query retrieves all pending leave requests along with the name of the user who made each request

        return view('admin.leave-requests', compact('leaveRequests'));
    }

    public function users()
    {
        $users = User::orderBy('role', 'asc')->orderBy('name', 'asc')->get();
        // Order users by role first, then by name alphabetically

        return view('admin.users', compact('users'));
    }

}
