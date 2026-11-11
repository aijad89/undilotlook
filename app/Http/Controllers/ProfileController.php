<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

/**
 * The participant's own profile: their account and company as the office keeps
 * them, the answers each request to join starts from, and their password.
 *
 * The office creates and keeps the account (there is no self sign-up), so the
 * facts here are read-only. The password is the one thing a participant
 * changes, since it is handed over by the office in the first place.
 */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return view('profile', ['user' => $user, 'company' => $user->company]);
    }

    /**
     * A routine change: the other devices stay signed in. Only a reset evicts
     * them (NewPasswordController), and that split was decided on purpose.
     */
    public function password(Request $request)
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.current_password' => 'That is not your current password.',
            'password.different' => 'Choose a password different from the current one.',
        ], [
            'password' => 'new password',
        ]);

        // The `hashed` cast hashes it.
        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password changed. Use the new one next time you sign in.');
    }
}
