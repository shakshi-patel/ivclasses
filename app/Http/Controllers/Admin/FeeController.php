<?php

namespace App\Http\Controllers\Admin;

use App\Models\Batch;
use App\Models\Branch;
use App\Models\FeeInvoice;
use App\Models\FeePayment;
use App\Models\Institute;
use App\Models\Student;
use App\Services\FeeService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeeController extends AdminController
{
    public function __construct(
        protected FeeService $feeService
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensureCan('fees.view');

        $payments = FeePayment::query()
            ->with([
                'student:id,name,admission_no,institute_id,branch_id,standard_id',
                'student.branch:id,name',
                'student.standard:id,name',
                'invoice:id,title,status,student_batch_id',
                'invoice.enrollment.batch:id,name,standard_id',
                'invoice.enrollment.batch.standard:id,name',
                'collector:id,name',
            ])
            ->when(auth()->user() && !auth()->user()->isPlatformUser(), function ($q) {
                $q->whereHas('student', fn($sq) => $sq->where('institute_id', auth()->user()->institute_id));
            })
            ->when(BranchScope::for()->isRestricted(), function ($q) {
                $ids = BranchScope::for()->branchIds();
                $q->whereHas('student', fn($sq) => $sq->whereIn('branch_id', $ids));
            })
            ->when($this->isPlatform() && $request->filled('institute_id'), function ($q) use ($request) {
                $q->whereHas('student', fn($sq) => $sq->where('institute_id', (int) $request->input('institute_id')));
            })
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereHas('student', fn($sq) => $sq->where('branch_id', (int) $request->input('branch_id')));
            })
            ->when($request->filled('batch_id'), function ($q) use ($request) {
                $q->whereHas('invoice.enrollment', fn($eq) => $eq->where('batch_id', (int) $request->input('batch_id')));
            })
            ->when($request->filled('payment_method'), fn($q) => $q->where('payment_method', $request->input('payment_method')))
            ->when($request->filled('start_date'), fn($q) => $q->whereDate('payment_date', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn($q) => $q->whereDate('payment_date', '<=', $request->input('end_date')))
            ->latest('payment_date')
            ->latest('id')
            ->get();

        $filters = $this->listFilterVars($request, ['branches', 'batches']);

        return view('admin.fees.fees', array_merge(compact('payments'), $filters));
    }

    public function create(): View
    {
        $this->ensureCanAny(['fees.add', 'fees_follow_up.add']);

        return view('admin.fees.add_fees', [
            'institutes' => $this->instituteOptions(),
            'branches' => $this->branchOptions(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->boolean('follow_up_only')) {
            $this->ensureCan('fees_follow_up.show');

            $validated = $request->validate([
                'student_id' => ['required', 'integer', 'exists:students,id'],
                'batch_id' => ['required', 'integer', 'exists:batches,id'],
                'remark' => ['required', 'string', 'max:2000'],
                'next_follow_up_at' => ['nullable', 'date'],
            ]);

            $this->assertStudentVisible((int) $validated['student_id']);

            $followUp = $this->feeService->followUpOnly($validated);

            if ($request->expectsJson()) {
                return Ajax::success('Follow-up saved.', $followUp);
            }

            return redirect()->route('admin.fees.follow_up')->with('success', 'Follow-up saved.');
        }

        $this->ensureCanAny(['fees.add', 'fees_follow_up.add']);

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'string', 'max:30'],
            'transaction_ref' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->assertStudentVisible((int) $validated['student_id']);

        try {
            $payment = $this->feeService->collect($validated);
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return Ajax::error($e->getMessage(), ['amount' => [$e->getMessage()]]);
            }

            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        if ($request->expectsJson()) {
            return Ajax::success('Payment recorded.', $payment, 201);
        }

        return redirect()->route('admin.fees')->with('success', 'Payment recorded.');
    }

    public function studentSummary(Request $request): JsonResponse
    {
        $this->ensureCanAny(['fees.add', 'fees_follow_up.add']);

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'batch_id' => ['required', 'integer', 'exists:batches,id'],
        ]);

        $student = Student::query()->findOrFail((int) $validated['student_id']);
        $this->assertStudentVisible($student->id);

        $summary = $this->feeService->studentSummary($student, (int) $validated['batch_id']);

        return Ajax::success('OK', $summary);
    }

    public function followUpIndex(Request $request): View
    {
        $this->ensureCan('fees_follow_up.view');

        $query = $this->invoiceQuery()
            ->with([
                'student.parent',
                'student.standard:id,name',
                'branch:id,name',
                'enrollment.batch:id,name',
                'latestFollowUp.creator:id,name',
                'followUps.creator:id,name',
            ])
            ->where('due_amount', '>', 0);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }

        if ($request->filled('batch_id')) {
            $query->whereHas('enrollment', fn($q) => $q->where('batch_id', (int) $request->input('batch_id')));
        }

        if ($request->filled('follow_up_date')) {
            $query->whereHas('latestFollowUp', fn($q) => $q->whereDate('next_follow_up_at', $request->input('follow_up_date')));
        }

        if ($request->input('status') === 'complete') {
            $query->where(function ($q) {
                $q->whereDoesntHave('latestFollowUp')
                    ->orWhereHas('latestFollowUp', fn($fq) => $fq->where('status', 'complete'));
            });
        } elseif ($request->input('status') === 'pending') {
            $query->whereHas('latestFollowUp', fn($q) => $q->where('status', 'pending'));
        }

        $this->applyInstituteFilter($query, $request);

        $invoices = $query
            ->orderByDesc('due_amount')
            ->get();

        $filters = $this->listFilterVars($request, ['branches']);

        return view('admin.fees.fees_follow_up', array_merge(compact('invoices'), $filters, [
            'instituteId' => $filters['filterInstituteId'] ?? null,
        ]));
    }

    public function followUpStore(Request $request, FeeInvoice $invoice): JsonResponse
    {
        $this->ensureCan('fees_follow_up.show');
        $this->assertInvoiceVisible($invoice);

        $validated = $request->validate([
            'remark' => ['required', 'string', 'max:2000'],
            'next_follow_up_at' => ['nullable', 'date'],
        ]);

        $followUp = $this->feeService->addFollowUp($invoice, [
            'remark' => $validated['remark'],
            'next_follow_up_at' => $validated['next_follow_up_at'] ?? null,
        ]);

        return Ajax::success('Follow-up saved.', $followUp);
    }

    public function receipt(Request $request, FeePayment $payment): JsonResponse|View
    {
        $this->ensureCan('fees.receipt');
        $this->loadFeeReceiptData($payment);
        $this->assertStudentVisible((int) $payment->student_id);

        if ($request->ajax() || $request->wantsJson()) {
            return Ajax::success('OK', [
                'title' => 'Fee Receipt — ' . ($payment->receipt_no ?? ('#' . $payment->id)),
                'html' => view('admin.fees.partials.receipt_card', compact('payment'))->render(),
                'export_url' => route('admin.fees.receipt_pdf', $payment),
            ]);
        }

        return view('admin.print.fee_receipt', compact('payment'));
    }

    public function receiptPdf(FeePayment $payment): Response
    {
        $this->ensureCan('fees.receipt');
        $this->loadFeeReceiptData($payment);
        $this->assertStudentVisible((int) $payment->student_id);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $payment->receipt_no ?: ('fee-' . $payment->id)) ?: 'fee-receipt';
        $filename = $safeName . '-' . now()->format('Ymd_His') . '.pdf';

        return Pdf::loadView('admin.print.pdf.fee_receipt', compact('payment'))
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    protected function loadFeeReceiptData(FeePayment $payment): void
    {
        $payment->load([
            'student.branch',
            'student.standard',
            'student.parent',
            'student.institute',
            'invoice.enrollment.batch',
            'collector:id,name',
        ]);
    }

    public function destroy(Request $request, FeePayment $payment): JsonResponse|RedirectResponse
    {
        $this->ensureCan('fees.delete');
        $this->assertStudentVisible((int) $payment->student_id);
        $this->feeService->deletePayment($payment);

        return $this->respondDestroyed($request, 'Fee payment deleted.', 'admin.fees');
    }

    public function followUpExportPdf(Request $request): Response
    {
        $this->ensureCan('fees_follow_up.export');

        $validated = $request->validate([
            'institute_id' => ['nullable', 'integer', 'exists:institutes,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => ['integer', 'exists:batches,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $user = auth()->user();
        $instituteId = null;
        if ($user && !$user->isPlatformUser()) {
            $instituteId = (int) $user->institute_id;
        } elseif (!empty($validated['institute_id'])) {
            $instituteId = (int) $validated['institute_id'];
        }

        $query = $this->invoiceQuery()
            ->with([
                'student.parent',
                'student.standard:id,name',
                'branch:id,name',
                'enrollment.batch:id,name',
                'latestFollowUp.creator:id,name',
            ])
            ->where('due_amount', '>', 0);

        if ($instituteId) {
            $query->where('institute_id', $instituteId);
        }

        if (!empty($validated['branch_id'])) {
            $query->where('branch_id', (int) $validated['branch_id']);
        }

        if (!empty($validated['batch_ids'])) {
            $batchIds = array_map('intval', $validated['batch_ids']);
            $query->whereHas('enrollment', fn($q) => $q->whereIn('batch_id', $batchIds));
        }

        if (!empty($validated['start_date']) || !empty($validated['end_date'])) {
            $start = $validated['start_date'] ?? '1900-01-01';
            $end = $validated['end_date'] ?? '2100-12-31';
            $query->where(function ($q) use ($start, $end) {
                $q->whereHas('latestFollowUp', function ($fq) use ($start, $end) {
                    $fq->whereDate('next_follow_up_at', '>=', $start)
                        ->whereDate('next_follow_up_at', '<=', $end);
                })->orWhere(function ($dq) use ($start, $end) {
                    $dq->whereDate('due_date', '>=', $start)
                        ->whereDate('due_date', '<=', $end);
                });
            });
        }

        $invoices = $query->orderByDesc('due_amount')->get();
        $filters = $validated;
        if ($instituteId) {
            $filters['institute_id'] = $instituteId;
        }

        $instituteName = $instituteId
            ? (Institute::query()->whereKey($instituteId)->value('name') ?? config('app.name'))
            : config('app.name');

        $branchName = !empty($validated['branch_id'])
            ? Branch::query()->whereKey((int) $validated['branch_id'])->value('name')
            : null;

        $batchLabel = null;
        if (!empty($validated['batch_ids'])) {
            $batchNames = Batch::query()
                ->whereIn('id', array_map('intval', $validated['batch_ids']))
                ->orderBy('name')
                ->pluck('name')
                ->all();
            $batchLabel = $batchNames ? implode(', ', $batchNames) : null;
        }

        $filename = 'fees-follow-up-' . now()->format('Ymd_His') . '.pdf';

        return Pdf::loadView('admin.print.pdf.fees_follow_up', compact(
            'invoices',
            'filters',
            'instituteName',
            'branchName',
            'batchLabel'
        ))
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    protected function invoiceQuery()
    {
        $query = FeeInvoice::query();

        $user = auth()->user();
        if ($user && !$user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        if (BranchScope::for()->isRestricted()) {
            $ids = BranchScope::for()->branchIds();
            $query->whereIn('branch_id', $ids);
        }

        return $query;
    }

    protected function instituteOptions()
    {
        $user = auth()->user();
        if ($user && !$user->isPlatformUser()) {
            return Institute::query()->where('id', $user->institute_id)->get(['id', 'name']);
        }

        return Institute::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function branchOptions(?int $instituteId = null)
    {
        $user = auth()->user();
        $instituteId ??= $user && !$user->isPlatformUser() ? $user->institute_id : null;

        if (!$instituteId) {
            return collect();
        }

        return BranchScope::for()->branchesForInstitute($instituteId);
    }

    protected function assertStudentVisible(int $studentId): void
    {
        $student = Student::query()->findOrFail($studentId);
        $user = auth()->user();

        if ($user && !$user->isPlatformUser() && (int) $student->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($student->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $student->branch_id);
        }
    }

    protected function assertInvoiceVisible(FeeInvoice $invoice): void
    {
        $user = auth()->user();
        if ($user && !$user->isPlatformUser() && (int) $invoice->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($invoice->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $invoice->branch_id);
        }
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
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->canAny($permissions))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

}
