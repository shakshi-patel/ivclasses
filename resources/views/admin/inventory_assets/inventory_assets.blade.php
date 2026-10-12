@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex justify-content-between gap-3 flex-wrap">
                    <h4 class="admin-page-title"><i class="bx bx-box"></i> Inventory &amp; Assets</h4>
                    <a href="{{ route('admin.inventory_assets.add') }}" class="btn admin-btn-primary">
                        <i class="bx bx-plus me-1"></i> Add Inventory &amp; Assets
                    </a>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Inventory &amp; Assets</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.inventory_assets')"
                    :show-institute="$isPlatform"
                    :show-branch="true"
                    :show-status="true"
                    :statuses="\App\Models\InventoryAsset::STATUSES"
                    :institutes="$institutes ?? collect()"
                    :branches="$branches ?? collect()"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar"><h5>Inventory &amp; Assets List</h5></div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sr.</th><th>Branch</th><th>Asset Name</th><th>Category</th><th>Qty</th>
                                    <th>Purchase Date</th><th>Cost (₹)</th><th>Remark</th><th>Status</th><th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($assets as $asset)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $asset->branch?->name ?? '—' }}</td>
                                        <td class="fw-semibold">{{ $asset->name }}</td>
                                        <td>{{ $asset->category?->name ?? '—' }}</td>
                                        <td>{{ $asset->quantity }}</td>
                                        <td>{{ $asset->purchase_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="fw-bold">₹{{ number_format((float) $asset->purchase_amount, 2) }}</td>
                                        <td>{{ $asset->note ?? '—' }}</td>
                                        <td>
                                            <span class="admin-status-pill {{ $asset->statusPillClass() }}">
                                                {{ $asset->statusLabel() }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('admin.inventory_assets.edit', $asset) }}" class="admin-action-icon" title="Edit"><i class="bx bx-pencil"></i></a>
                                            <button type="button" class="admin-action-icon btn-ia-delete" title="Delete"
                                                data-url="{{ route('admin.inventory_assets.destroy', $asset) }}"><i class="bx bx-trash"></i></button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="text-center text-muted py-5">No assets yet.</td></tr>
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
document.querySelectorAll('.btn-ia-delete').forEach(btn => {
    btn.addEventListener('click', async function() {
        const ok = await AdminAjax.confirm({
            title: 'Delete asset?',
            text: 'This asset will be removed.',
            confirmText: 'Yes, delete',
            icon: 'warning'
        });
        if (!ok) return;
        try {
            const data = await AdminAjax.destroy(btn.dataset.url);
            AdminAjax.removeRow(btn.closest('tr'));
            await AdminAjax.success(data.message || 'Asset deleted.');
        } catch (err) {
            await AdminAjax.error(err.message || 'Delete failed.');
        }
    });
});
</script>
@endpush
