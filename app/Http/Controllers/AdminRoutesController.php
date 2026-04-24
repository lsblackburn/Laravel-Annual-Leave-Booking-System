<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Leave;
use App\Models\User;

class AdminRoutesController extends Controller
{

    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function leaveRequests()
    {
        $leaveRequests = Leave::select('leaves.*', 'users.name as user_name')
            ->join('users', 'leaves.user_id', '=', 'users.id')
            ->where('leaves.status', 'pending')
            ->get();

        return view('admin.leave-requests', compact('leaveRequests'));
    }

    public function users()
    {
        $users = User::all();

        return view('admin.users', compact('users'));
    }

}
