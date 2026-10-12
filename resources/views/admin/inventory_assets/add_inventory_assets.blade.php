@extends('admin.layouts.layout')

@section('content')
    @php
        $isEdit = isset($asset) && $asset;
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title"><i class="bx bx-box"></i> {{ $isEdit ? 'Edit' : 'Add' }} Inventory &amp; Assets</h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.inventory_assets') }}">Inventory</a></li>
                        <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Add' }}</li>
                    </ol>
                </nav>

                @if ($errors->any())
                    <div class="alert alert-danger mt-3"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif

                <div class="master-stu-form-wrap">
                    <a href="{{ route('admin.inventory_assets') }}" class="btn btn-sm stu-top-back admin-btn-primary">
                        <i class="bx bx-left-arrow-alt me-1"></i> Back
                    </a>
                    <div class="master-stu-form-card">
                        <div class="stu-form-head">{{ $isEdit ? 'Edit' : 'Add' }} Asset</div>
                        <div class="stu-form-body">
                            <form method="POST"
                                action="{{ $isEdit ? route('admin.inventory_assets.update', $asset) : route('admin.inventory_assets.store') }}"
                                id="inventoryAssetForm"
                                data-institute-cascade
                                data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}">
                                @csrf
                                @if ($isEdit) @method('PUT') @endif
                                <div class="row g-3">
                                    @include('admin.partials.form_institute_field', [
                                        'instituteId' => old('institute_id', $asset?->institute_id),
                                        'institutes' => $institutes,
                                        'label' => 'Classes / Institute',
                                    ])
                                    @include('admin.partials.form_branch_field', [
                                        'branchId' => old('branch_id', $asset?->branch_id),
                                        'branches' => $branches,
                                        'required' => false,
                                    ])
                                    <div class="col-md-6">
                                        <label class="form-label">Asset Name <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="name" required
                                            value="{{ old('name', $asset?->name) }}" placeholder="Enter asset name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Category <span class="req">*</span></label>
                                        <select class="form-select" name="category_id" required>
                                            <option value="">Select category</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}" @selected(old('category_id', $asset?->category_id) == $category->id)>{{ $category->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Quantity <span class="req">*</span></label>
                                        <input type="number" class="form-control" name="quantity" min="1" required
                                            value="{{ old('quantity', $asset?->quantity ?? 1) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Purchase Date <span class="req">*</span></label>
                                        <input type="date" class="form-control" name="purchase_date" required
                                            value="{{ old('purchase_date', optional($asset?->purchase_date)->format('Y-m-d') ?? date('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Purchase Cost (₹) <span class="req">*</span></label>
                                        <input type="number" class="form-control" name="purchase_amount" min="0" step="0.01" required
                                            value="{{ old('purchase_amount', $asset?->purchase_amount) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status <span class="req">*</span></label>
                                        <select class="form-select" name="status" required data-no-search>
                                            @foreach (\App\Models\InventoryAsset::STATUSES as $value => $label)
                                                <option value="{{ $value }}" @selected(old('status', $asset?->status ?? \App\Models\InventoryAsset::STATUS_WORKING) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Remark</label>
                                        <input type="text" class="form-control" name="note" value="{{ old('note', $asset?->note) }}">
                                    </div>
                                </div>
                                <div class="master-stu-form-actions mt-4">
                                    <button type="submit" class="btn admin-btn-primary px-4"><i class="bx bx-save me-1"></i> Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('inventoryAssetForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = this.querySelector('[type="submit"]');
    if (btn) btn.disabled = true;
    try {
        await AdminAjax.submitForm(this, { redirectUrl: @json(route('admin.inventory_assets')) });
    } catch (err) {
        if (btn) btn.disabled = false;
    }
});
</script>
@endpush
