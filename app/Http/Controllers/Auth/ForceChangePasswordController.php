<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForceChangePasswordController extends Controller
{
    public function edit(): View|RedirectResponse
    {
        $user = auth()->user();
        if (! $user || ! $user->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.force-change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->must_change_password, 403);

        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user->update([
            'password' => $validated['password'],
            'must_change_password' => false,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Password updated. Welcome to Class Flow.');
    }
}
