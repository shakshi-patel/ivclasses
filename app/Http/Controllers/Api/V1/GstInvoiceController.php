<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\GstInvoiceResource;
use App\Models\FeePayment;
use App\Models\GstInvoice;
use App\Services\GstInvoiceService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GstInvoiceController extends ApiController
{
    public function __construct(
        protected GstInvoiceService $gstService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('gst_invoice.view');

        $query = GstInvoice::query()
            ->where('institute_id', $this->actor()->institute_id)
            ->with([
                'student:id,name,standard_id,parent_id',
                'student.standard:id,name',
                'student.parent:id,father_name,phone',
                'payment:id,receipt_no,amount,payment_date',
                'branch:id,name',
            ]);

        $scope = BranchScope::for($this->actor());
        if ($scope->isRestricted()) {
            $query->whereIn('branch_id', $scope->branchIds() ?: [0]);
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->input('student_id'));
        }

        return $this->paginated(
            'OK',
            $query->latest('id')->paginate($this->perPage())
                ->through(fn (GstInvoice $row) => (new GstInvoiceResource($row))->resolve($request))
        );
    }

    public function show(Request $request, GstInvoice $gstInvoice): JsonResponse
    {
        $this->ensureCan('gst_invoice.view');
        $this->assertVisible($gstInvoice);

        $gstInvoice->load([
            'student.standard:id,name',
            'student.parent:id,father_name,phone',
            'payment:id,receipt_no,amount,payment_date',
            'branch:id,name',
            'institute:id,name,gstin,address',
        ]);

        return Ajax::success('OK', array_merge(
            (new GstInvoiceResource($gstInvoice))->resolve($request),
            [
                'institute' => $gstInvoice->institute ? [
                    'id' => $gstInvoice->institute->id,
                    'name' => $gstInvoice->institute->name,
                    'gstin' => $gstInvoice->institute->gstin,
                    'address' => $gstInvoice->institute->address,
                ] : null,
            ]
        ));
    }

    public function generate(FeePayment $payment): JsonResponse
    {
        $this->ensureCanAny(['gst_invoice.add', 'gst_invoice.generate']);
        $this->assertPaymentVisible($payment);

        $existing = GstInvoice::query()->where('fee_payment_id', $payment->id)->first();
        if ($existing) {
            return Ajax::success('GST invoice already exists.', (new GstInvoiceResource($existing->load([
                'student:id,name,standard_id',
                'payment:id,receipt_no,amount,payment_date',
            ])))->resolve());
        }

        $invoice = $this->gstService->generateFromPayment($payment);

        return Ajax::success('GST invoice generated.', (new GstInvoiceResource($invoice->load([
            'student:id,name,standard_id',
            'payment:id,receipt_no,amount,payment_date',
        ])))->resolve(), 201);
    }

    protected function assertVisible(GstInvoice $gstInvoice): void
    {
        if ((int) $gstInvoice->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'GST invoice not found.');
        }
        if ($gstInvoice->branch_id) {
            BranchScope::for($this->actor())->assertCanAccessBranch((int) $gstInvoice->branch_id);
        }
    }

    protected function assertPaymentVisible(FeePayment $payment): void
    {
        $payment->loadMissing('student');
        abort_unless($payment->student, 404, 'Payment not found.');
        if ((int) $payment->student->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Payment not found.');
        }
        if ($payment->student->branch_id) {
            BranchScope::for($this->actor())->assertCanAccessBranch((int) $payment->student->branch_id);
        }
    }
}
