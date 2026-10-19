@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title"><i class="bx bx-receipt"></i> GST Invoice</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">GST Invoice</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.gst_invoice')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :institutes="$institutes ?? collect()"
                    :branches="$branches ?? collect()"
                >
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="date" class="form-control form-control-sm" name="start_date" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" class="form-control form-control-sm" name="end_date" value="{{ request('end_date') }}">
                    </div>
                </x-admin.list-filters>

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>GST Invoice List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Student Name</th>
                                    <th>Father Name</th>
                                    <th>Contact No.</th>
                                    <th>Payment Amount</th>
                                    <th>GST Invoice</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $payment)
                                    @php $student = $payment->student; @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="fw-semibold">{{ $student?->name ?? '—' }}</td>
                                        <td>{{ $student?->parent?->father_name ?? '—' }}</td>
                                        <td>{{ $student?->parent?->phone ?? '—' }}</td>
                                        <td class="fw-bold" style="color: var(--iv-primary-dark);">₹{{ number_format((float) $payment->amount, 2) }}</td>
                                        <td>
                                            @if ($payment->gstInvoice)
                                                <span class="admin-status-pill is-active">{{ $payment->gstInvoice->invoice_no }}</span>
                                            @else
                                                <span class="text-muted small">Not generated</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($payment->gstInvoice)
                                                <a href="{{ route('admin.gst_invoice.show', $payment->gstInvoice) }}" class="admin-action-icon" title="View">
                                                    <i class="bx bx-show"></i>
                                                </a>
                                            @else
                                                <button type="button" class="admin-action-icon btn-gst-generate" title="Generate GST"
                                                    data-url="{{ route('admin.gst_invoice.generate', $payment) }}">
                                                    <i class="bx bx-plus-circle"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-5">No fee payments found. Collect fees first.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    document.addEventListener('click', async function(e) {
        var btn = e.target.closest('.btn-gst-generate');
        if (!btn) return;

        e.preventDefault();
        if (btn.disabled) return;

        var url = btn.dataset.url;
        if (!url) {
            await AdminAjax.error('Generate URL is missing.');
            return;
        }

        var ok = await AdminAjax.confirm({
            title: 'Generate GST invoice?',
            text: 'Create a GST invoice for this fee payment.',
            confirmText: 'Yes, generate',
            icon: 'question'
        });
        if (!ok) return;

        btn.disabled = true;
        try {
            var data = await AdminAjax.post(url);
            await AdminAjax.success(data.message || 'GST invoice generated.');
            window.location.reload();
        } catch (err) {
            await AdminAjax.error(err.message || 'Failed to generate GST invoice.');
            btn.disabled = false;
        }
    });
})();
</script>
@endpush
