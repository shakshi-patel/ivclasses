<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Ajax;
use App\Support\OwnBatchScope;
use App\Support\ParentBatchScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

abstract class ApiController extends Controller
{
    protected function actor(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    protected function ensureCan(string $permission): void
    {
        $this->ensureCanAny([$permission]);
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function ensureCanAny(array $permissions): void
    {
        $user = $this->actor();
        if ($user->canAny($permissions)) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    protected function paginated(string $message, LengthAwarePaginator $paginator, array $extra = []): JsonResponse
    {
        return Ajax::success($message, array_merge([
            'items' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], $extra));
    }

    protected function perPage(): int
    {
        return min(50, max(1, (int) request()->input('per_page', 15)));
    }

    /**
     * Teachers may only write into assigned batches (class or subject teacher).
     *
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    protected function assignedBatchIdRules(?int $studentId = null): array
    {
        $user = $this->actor();
        $rules = ['required', 'integer', 'exists:batches,id'];
        if ($user->isTeacher()) {
            $rules[] = Rule::in(OwnBatchScope::batchIds($user) ?: [0]);
        }
        if ($user->isParent()) {
            $ids = ParentBatchScope::batchIds($user, $studentId)->map(fn ($id) => (int) $id)->all();
            $rules[] = Rule::in($ids !== [] ? $ids : [0]);
        }

        return $rules;
    }

    protected function parentStudentId(?int $studentId = null): ?int
    {
        $user = $this->actor();
        if (! $user->isParent()) {
            return null;
        }

        $id = $studentId ?? ((int) request()->query('student_id', 0) ?: null);
        if ($id) {
            ParentBatchScope::assertOwnsStudent($user, $id);
        }

        return $id;
    }
}
