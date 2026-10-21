<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Services\Mobile\MobileUserAccountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class AccountProfileController extends AdminController
{
    public function __construct(
        protected MobileUserAccountService $accounts
    ) {}

    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'personal');
        if (! in_array($tab, ['personal', 'password'], true)) {
            $tab = 'personal';
        }

        return view('admin.profile.index', [
            'tab' => $tab,
            'profileUser' => User::query()->findOrFail($request->user()->id),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user = User::query()->findOrFail($request->user()->id);

        $fields = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
        ];

        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $fields['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        try {
            $result = $this->accounts->updateProfile($user, $fields);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        auth()->setUser($result['user']);

        return redirect()
            ->route('admin.profile', ['tab' => 'personal'])
            ->with('success', 'Profile updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ]);

        $user = User::query()->findOrFail($request->user()->id);

        if (! Hash::check($data['current_password'], $user->getAuthPassword())) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        try {
            $this->accounts->updatePassword($user, $data['password']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.profile', ['tab' => 'password'])
            ->with('success', 'Password updated.');
    }
}
