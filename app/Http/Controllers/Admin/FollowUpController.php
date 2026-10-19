<?php

namespace App\Http\Controllers\Admin;

use App\Models\Inquiry;
use App\Services\InquiryService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends AdminController
{
    public function __construct(
        protected InquiryService $inquiryService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('follow_up.view');

        $query = $this->inquiryQuery()
            ->with(['standard:id,name', 'branch:id,name', 'followUps.creator:id,name'])
            ->whereNotIn('status', ['converted', 'lost']);

        $this->applyInstituteFilter($query, $request);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('follow_up_date')) {
            $query->whereDate('next_follow_up_at', $request->input('follow_up_date'));
        }

        if ($request->input('status') === 'complete') {
            $query->whereNull('next_follow_up_at');
        } elseif ($request->input('status') === 'pending') {
            $query->whereNotNull('next_follow_up_at');
        }

        $inquiries = $query
            ->orderByRaw('next_follow_up_at IS NULL')
            ->orderBy('next_follow_up_at')
            ->get();

        $filters = $this->listFilterVars($request, ['branches']);

        return view('admin.follow_up.follow_up', array_merge(compact('inquiries'), $filters));
    }

    public function store(Request $request, Inquiry $inquiry): JsonResponse
    {
        $this->ensureCan('follow_up.add');
        $this->assertVisible($inquiry);

        $inquiryDate = optional($inquiry->inquiry_date)->format('Y-m-d');

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:2000'],
            'next_follow_up_at' => array_values(array_filter([
                'nullable',
                'date',
                $inquiryDate ? 'after_or_equal:'.$inquiryDate : null,
            ])),
        ], [
            'next_follow_up_at.after_or_equal' => 'The follow-up date must be on or after the enquiry date.',
        ]);

        $followUp = $this->inquiryService->addFollowUp($inquiry, [
            'follow_up_at' => now(),
            'remark' => $validated['remark'],
            'next_follow_up_at' => $validated['next_follow_up_at'] ?? null,
        ]);

        return Ajax::success('Follow-up saved.', $followUp);
    }

    protected function inquiryQuery()
    {
        $scope = BranchScope::for();
        $query = Inquiry::query();

        if (! $scope->isRestricted()) {
            return $query;
        }

        $ids = $scope->branchIds();
        if ($ids === []) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $ids);
    }

    protected function assertVisible(Inquiry $inquiry): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $inquiry->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($inquiry->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $inquiry->branch_id);
        }
    }

    protected function ensureCan(string $permission): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can($permission))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
