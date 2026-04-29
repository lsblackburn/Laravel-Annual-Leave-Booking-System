<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, ?User $user = null): RedirectResponse
    {

        $targetUser = $user ?? $request->user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => $user ? ['nullable'] : ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $targetUser->update([
            'password' => Hash::make($validated['password']),
        ]);

        if ($user) {
            return redirect()->route('admin.users.edit', $targetUser)->with('success', 'Password updated successfully.');
        }

        return back()->with('status', 'password-updated');
    }
}
