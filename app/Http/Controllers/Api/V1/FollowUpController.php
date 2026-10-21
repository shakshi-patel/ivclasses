<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\InquiryFollowUpResource;
use App\Http\Resources\Api\V1\InquiryResource;
use App\Models\Inquiry;
use App\Services\InquiryService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowUpController extends ApiController
{
    public function __construct(
        protected InquiryService $inquiryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('follow_up.view');

        $query = $this->inquiryQuery()
            ->where('institute_id', $this->actor()->institute_id)
            ->with(['branch:id,name', 'standard:id,name', 'followUps.creator:id,name'])
            ->whereNotIn('status', ['converted', 'lost']);

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

        return $this->paginated(
            'OK',
            $query->orderByRaw('next_follow_up_at IS NULL')
                ->orderBy('next_follow_up_at')
                ->paginate($this->perPage())
                ->through(fn (Inquiry $inquiry) => (new InquiryResource($inquiry))->resolve($request))
        );
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
        $followUp->load('creator:id,name');

        return Ajax::success('Follow-up saved.', (new InquiryFollowUpResource($followUp))->resolve(), 201);
    }

    protected function inquiryQuery()
    {
        $scope = BranchScope::for($this->actor());
        $query = Inquiry::query();
        if (! $scope->isRestricted()) {
            return $query;
        }

        $ids = $scope->branchIds();

        return $ids === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $ids);
    }

    protected function assertVisible(Inquiry $inquiry): void
    {
        if ((int) $inquiry->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Inquiry not found.');
        }

        if ($inquiry->branch_id) {
            BranchScope::for($this->actor())->assertCanAccessBranch((int) $inquiry->branch_id);
        }
    }
}
