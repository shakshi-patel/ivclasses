<?php

namespace App\Http\Controllers\Admin;

use App\Models\FeePayment;
use App\Models\GstInvoice;
use App\Services\GstInvoiceService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GstInvoiceController extends AdminController
{
    public function __construct(
        protected GstInvoiceService $gstService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('gst_invoice.view');

        $payments = FeePayment::query()
            ->with([
                'student:id,name,institute_id,branch_id,parent_id',
                'student.parent:id,father_name,phone',
                'gstInvoice:id,fee_payment_id,invoice_no,total',
            ])
            ->when(auth()->user() && ! auth()->user()->isPlatformUser(), function ($q) {
                $q->whereHas('student', fn ($sq) => $sq->where('institute_id', auth()->user()->institute_id));
            })
            ->when(BranchScope::for()->isRestricted(), function ($q) {
                $ids = BranchScope::for()->branchIds();
                $q->whereHas('student', fn ($sq) => $sq->whereIn('branch_id', $ids));
            })
            ->when($this->isPlatform() && $request->filled('institute_id'), function ($q) use ($request) {
                $q->whereHas('student', fn ($sq) => $sq->where('institute_id', (int) $request->input('institute_id')));
            })
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereHas('student', fn ($sq) => $sq->where('branch_id', (int) $request->input('branch_id')));
            })
            ->when($request->filled('start_date'), fn ($q) => $q->whereDate('payment_date', '>=', $request->input('start_date')))
            ->when($request->filled('end_date'), fn ($q) => $q->whereDate('payment_date', '<=', $request->input('end_date')))
            ->latest('payment_date')
            ->latest('id')
            ->get();

        $filters = $this->listFilterVars($request, ['branches']);

        return view('admin.gst_invoice.gst_invoice', array_merge(compact('payments'), $filters));
    }

    public function generate(FeePayment $payment): JsonResponse
    {
        $this->ensureCan('gst_invoice.generate');
        $this->assertPaymentVisible($payment);

        if ($payment->gstInvoice) {
            return Ajax::success('GST invoice already exists.', $payment->gstInvoice);
        }

        $invoice = $this->gstService->generateFromPayment($payment);

        return Ajax::success('GST invoice generated.', $invoice, 201);
    }

    public function show(GstInvoice $gstInvoice): View
    {
        $this->ensureCan('gst_invoice.view');
        $this->assertInvoiceVisible($gstInvoice);

        $gstInvoice->load([
            'student.parent',
            'student.standard:id,name',
            'payment',
            'branch:id,name',
            'institute:id,name,gstin,address,city,state,pincode',
        ]);

        return view('admin.gst_invoice.show', compact('gstInvoice'));
    }

    protected function assertPaymentVisible(FeePayment $payment): void
    {
        $payment->loadMissing('student');
        $student = $payment->student;
        abort_unless($student, 404);

        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $student->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($student->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $student->branch_id);
        }
    }

    protected function assertInvoiceVisible(GstInvoice $gstInvoice): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $gstInvoice->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($gstInvoice->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $gstInvoice->branch_id);
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
