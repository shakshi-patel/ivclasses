<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserMeResource;
use App\Models\User;
use App\Services\Mobile\MobileUserAccountService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProfileController extends Controller
{
    public function __construct(
        protected MobileUserAccountService $accounts
    ) {}

    public function update(Request $request): JsonResponse
    {
        /** @var User $authUser */
        $authUser = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ]);

        $fields = [];
        if (array_key_exists('name', $data)) {
            $fields['name'] = $data['name'];
        }
        if (array_key_exists('phone', $data)) {
            $fields['phone'] = $data['phone'];
        }

        if ($request->hasFile('avatar')) {
            $current = User::query()->findOrFail($authUser->id);
            if ($current->avatar_path) {
                Storage::disk('public')->delete($current->avatar_path);
            }
            $fields['avatar_path'] = $request->file('avatar')->store('avatars', 'public');
        }

        if ($fields === []) {
            return Ajax::error('Send at least one of: name, phone, avatar.', null, 422);
        }

        try {
            $result = $this->accounts->updateProfile($authUser, $fields);
        } catch (RuntimeException $e) {
            return Ajax::error($e->getMessage(), null, 500);
        }

        return Ajax::success('Profile updated.', [
            'user' => (new UserMeResource($result['user']))->resolve($request),
            'database' => $this->accounts->databaseName(),
            'synced' => $result['synced'],
        ]);
    }
}
