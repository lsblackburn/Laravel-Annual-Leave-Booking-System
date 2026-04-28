<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
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

    public function update(ProfileUpdateRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return redirect()
            ->route('admin.users')
            ->with('success', 'User updated successfully.');
    }

}
