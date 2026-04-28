<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class UserManagementController extends Controller
{
    public function promote(User $user)
    {
        $user->role = 'admin';
        $user->save();

        return redirect()->route('admin.users')->with('success', 'User promoted to admin successfully.');
    }

    public function demote(User $user)
    {
        $user->role = 'employee';
        $user->save();

        return redirect()->route('admin.users')->with('success', 'User demoted to regular user successfully.');
    }
}
