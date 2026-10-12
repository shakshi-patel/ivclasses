@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y admin-list-page">
        <div class="admin-main-card admin-list-toolbar">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-purchase-tag-alt"></i>
                            Plans
                        </h4>
                    </div>
                    @can('plans.add')
                        <a href="{{ route('admin.plans.create') }}" class="btn admin-btn-primary">
                            <i class="bx bx-plus me-1"></i> Add Plan
                        </a>
                    @endcan
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Plans</li>
                    </ol>
                </nav>

                <x-admin.list-filters
                    :action="route('admin.plans.index')"
                    :show-status="true"
                    :statuses="['active' => 'Active', 'inactive' => 'Inactive']"
                />

            </div>
        </div>

        <div class="admin-main-card admin-list-table-card">
            <div class="admin-main-card-inner">
                <div class="admin-table-wrap">
                    <div class="admin-list-card-bar">
                        <h5>Plans List</h5>
                    </div>
                    <table class="table datatables-basic admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sr</th>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Monthly</th>
                                    <th>Yearly</th>
                                    <th>Modules</th>
                                    <th>Limits</th>
                                    <th>Public</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($plans as $i => $plan)
                                    @php
                                        $mods = $plan->modules_json ?? [];
                                        $modLabel = in_array('*', $mods, true)
                                            ? 'All features'
                                            : (count($mods) ? count($mods).' features' : 'Core only');
                                    @endphp
                                    <tr data-plan-id="{{ $plan->id }}">
                                        <td>{{ $i + 1 }}</td>
                                        <td class="fw-semibold">{{ $plan->name }}</td>
                                        <td><code>{{ $plan->code }}</code></td>
                                        <td>₹{{ number_format((float) $plan->price_monthly, 0) }}</td>
                                        <td>₹{{ number_format((float) $plan->price_yearly, 0) }}</td>
                                        <td>
                                            <span class="text-muted small" title="{{ in_array('*', $mods, true) ? 'All' : implode(', ', $mods) }}">
                                                {{ $modLabel }}
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            B: {{ $plan->max_branches ?? '∞' }}
                                            · S: {{ $plan->max_students ?? '∞' }}
                                            · Staff: {{ $plan->max_staff ?? '∞' }}
                                        </td>
                                        <td>{{ $plan->is_public ? 'Yes' : 'No' }}</td>
                                        <td>
                                            <span class="admin-status-pill {{ $plan->status === 'active' ? 'is-active' : 'is-navy' }}">
                                                {{ $plan->status }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @can('plans.edit')
                                                <a href="{{ route('admin.plans.edit', $plan) }}" class="admin-action-icon" title="Edit">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                            @endcan
                                            @can('plans.delete')
                                                <button type="button" class="admin-action-icon btn-delete-plan"
                                                    title="Delete" data-id="{{ $plan->id }}">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">No plans yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    $planDeleteUrl = route('admin.plans.destroy', ['plan' => '__ID__']);
@endphp

@push('scripts')
    <script>
        (function() {
            const deleteUrl = @json($planDeleteUrl);

            document.querySelectorAll('.btn-delete-plan').forEach(function(btn) {
                btn.addEventListener('click', async function() {
                    const ok = await AdminAjax.confirm({
                        title: 'Delete plan?',
                        text: 'Plans with subscriptions cannot be deleted.',
                        confirmText: 'Yes, delete'
                    });
                    if (!ok) return;
                    try {
                        const data = await AdminAjax.destroy(deleteUrl.replace('__ID__', btn.dataset.id));
                        await AdminAjax.success(data.message || 'Deleted.');
                        btn.closest('tr')?.remove();
                    } catch (e) {
                        await AdminAjax.error(e.message);
                    }
                });
            });
        })();
    </script>
@endpush
