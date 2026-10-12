@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        {{-- TOP: title, breadcrumb, filters, summary --}}
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <h4 class="admin-page-title mb-0"><i class="bx bx-wallet"></i> Income &amp; Expense</h4>
                    <button type="button" class="btn admin-btn-primary" data-bs-toggle="modal" data-bs-target="#addIncomeExpenseModal">
                        <i class="bx bx-plus me-1"></i> Add Income &amp; Expense
                    </button>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Income &amp; Expense</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.income_expense')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :institutes="$institutes ?? collect()"
                    :branches="$branches ?? collect()"
                >
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">Type</label>
                        <select class="form-select form-select-sm" name="type">
                            <option value="">All</option>
                            <option value="income" @selected(request('type') === 'income')>Income</option>
                            <option value="expense" @selected(request('type') === 'expense')>Expense</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">Category</label>
                        <select class="form-select form-select-sm" name="category_id">
                            <option value="">All</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">Start Date</label>
                        <input type="date" class="form-control form-control-sm" name="start_date" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-6 col-lg-2">
                        <label class="form-label fw-semibold">End Date</label>
                        <input type="date" class="form-control form-control-sm" name="end_date" value="{{ request('end_date') }}">
                    </div>
                </x-admin.list-filters>

                <div class="row g-3 admin-list-summary">
                    <div class="col-md-4">
                        <div class="ie-summary-card is-income">
                            <div class="ico"><i class="bx bx-trending-up"></i></div>
                            <div>
                                <div class="lbl">Total Income</div>
                                <div class="val">₹{{ number_format((float) ($totals['income'] ?? 0), 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ie-summary-card is-expense">
                            <div class="ico"><i class="bx bx-trending-down"></i></div>
                            <div>
                                <div class="lbl">Total Expense</div>
                                <div class="val">₹{{ number_format((float) ($totals['expense'] ?? 0), 2) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="ie-summary-card is-balance">
                            <div class="ico"><i class="bx bx-wallet-alt"></i></div>
                            <div>
                                <div class="lbl">Net Balance</div>
                                <div class="val">₹{{ number_format((float) ($totals['balance'] ?? 0), 2) }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- BOTTOM: table only --}}
        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Income &amp; Expense List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0" style="min-width: 1100px;">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Collected By</th>
                                <th>Purpose</th>
                                <th>Payment Mode</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($entries as $entry)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">{{ $entry->title ?? $entry->category?->name }}</td>
                                    <td>{{ $entry->entry_date?->format('d M Y') }}</td>
                                    <td>{{ $entry->creator?->name ?? '—' }}</td>
                                    <td>{{ $entry->category?->name ?? '—' }}</td>
                                    <td><span class="stu-method-pill">{{ $entry->payment_method }}</span></td>
                                    <td>
                                        @if (strtolower((string) $entry->type) === 'income')
                                            <span class="admin-status-pill is-active">Income</span>
                                        @else
                                            <span class="admin-status-pill is-navy">Expense</span>
                                        @endif
                                    </td>
                                    <td class="fw-bold" style="color: {{ strtolower((string) $entry->type) === 'income' ? '#1f9d62' : '#c0392b' }};">
                                        ₹{{ number_format((float) $entry->amount, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="admin-action-icon btn-ie-delete" title="Delete"
                                            data-url="{{ route('admin.income_expense.destroy', $entry) }}"><i class="bx bx-trash"></i></button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-5">No entries yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade admin-modal" id="addIncomeExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Income &amp; Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="ieAddForm" method="POST" action="{{ route('admin.income_expense.store') }}">
                    @csrf
                    <div class="modal-body">
                        <ul class="nav nav-pills gap-2 mb-3 ie-modal-tabs">
                            <li class="nav-item"><button type="button" class="nav-link active" data-ie-type="income">Income</button></li>
                            <li class="nav-item"><button type="button" class="nav-link" data-ie-type="expense">Expense</button></li>
                        </ul>
                        <input type="hidden" name="institute_id" value="{{ auth()->user()?->institute_id }}">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select class="form-select" name="category_id" id="ieCategoryId" required>
                                <option value="">Select category</option>
                                @foreach ($categories->where('type', 'income') as $cat)
                                    <option value="{{ $cat->id }}" data-type="income">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Title</label>
                            <input type="text" class="form-control" name="title" placeholder="Optional title">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" min="0.01" step="0.01" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="entry_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" required data-no-search>
                                @foreach (['Cash', 'Online', 'UPI', 'Bank Transfer', 'Cheque'] as $mode)
                                    <option value="{{ $mode }}">{{ $mode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label fw-semibold">Remarks</label>
                            <textarea class="form-control" name="note" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn admin-btn-primary"><i class="bx bx-save me-1"></i> Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function() {
    const categories = @json($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'type' => $c->type]));
    const catSelect = document.getElementById('ieCategoryId');

    function fillCategories(type) {
        catSelect.innerHTML = '<option value="">Select category</option>';
        categories.filter(c => c.type === type).forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id; opt.textContent = c.name; catSelect.appendChild(opt);
        });
        document.querySelectorAll('[data-ie-type]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.ieType === type);
        });
    }

    document.querySelectorAll('[data-ie-type]').forEach(btn => {
        btn.addEventListener('click', () => fillCategories(btn.dataset.ieType));
    });

    document.getElementById('ieAddForm')?.addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn = this.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
        try {
            await AdminAjax.submitForm(this, { redirectUrl: @json(route('admin.income_expense')) });
        } catch (err) {
            if (btn) btn.disabled = false;
        }
    });

    document.querySelectorAll('.btn-ie-delete').forEach(btn => {
        btn.addEventListener('click', async function() {
            const ok = await AdminAjax.confirm({
                title: 'Delete entry?',
                text: 'This income/expense entry will be removed.',
                confirmText: 'Yes, delete',
                icon: 'warning'
            });
            if (!ok) return;
            try {
                const data = await AdminAjax.destroy(btn.dataset.url);
                AdminAjax.removeRow(btn.closest('tr'));
                await AdminAjax.success(data.message || 'Entry deleted.');
            } catch (err) {
                await AdminAjax.error(err.message || 'Delete failed.');
            }
        });
    });
})();
</script>
@endpush
