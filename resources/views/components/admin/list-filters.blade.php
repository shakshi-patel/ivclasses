{{--
  Shared list filters (student-style).
  Platform: Institute select + cascade to branch/standard/batch.
  Institute admin: NO visible institute field (scoped automatically);
  when Batch/Branch cascade is needed, hidden institute_id drives lookups.
--}}
@props([
    'action',
    'showInstitute' => false,
    'showBranch' => false,
    'showStandard' => false,
    'showBatch' => false,
    'showStatus' => false,
    'statuses' => [],
    'institutes' => null,
    'branches' => null,
    'standards' => null,
    'batches' => null,
])

@php
    $institutes = $institutes ?? collect();
    $branches = collect($branches ?? [])->unique('id')->values();
    $standards = collect($standards ?? [])->unique('id')->values();
    $batches = collect($batches ?? [])->unique('id')->values();

    $scopedInstituteId = null;
    if (! $showInstitute) {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && $user->institute_id) {
            $scopedInstituteId = (int) $user->institute_id;
        }
    }

    $useCascade = (bool) $showInstitute || ($scopedInstituteId && ($showBranch || $showStandard || $showBatch));
@endphp

<div class="admin-filter-section">
    <form method="GET"
        action="{{ $action }}"
        @if ($useCascade)
            data-institute-cascade
            @if ($showBatch) data-filter-batches-by-branch @endif
            data-cascade-url="{{ \App\Support\AdminUrl::lookups() }}"
        @endif
        class="admin-list-filter-form">
        <div class="row g-3 align-items-end">
            @if ($showInstitute)
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-semibold">Institute</label>
                    <select class="form-select form-select-sm"
                        name="institute_id"
                        data-cascade-source
                        data-placeholder="All Institutes">
                        <option value="">All Institutes</option>
                        @foreach ($institutes as $inst)
                            <option value="{{ $inst->id }}" @selected((string) request('institute_id') === (string) $inst->id)>
                                {{ $inst->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @elseif ($scopedInstituteId && $useCascade)
                <input type="hidden" name="institute_id" value="{{ $scopedInstituteId }}" data-cascade-source>
            @endif

            @if ($showBranch)
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-semibold">Branch</label>
                    <select class="form-select form-select-sm"
                        name="branch_id"
                        @if ($useCascade)
                            data-cascade-target="branches"
                            data-placeholder="All Branches"
                            data-keep-value="{{ request('branch_id') }}"
                        @endif>
                        <option value="">All Branches</option>
                        @unless ($useCascade)
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" @selected((string) request('branch_id') === (string) $b->id)>
                                    {{ $b->name }}
                                </option>
                            @endforeach
                        @endunless
                    </select>
                </div>
            @endif

            @if ($showStandard)
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-semibold">Standard</label>
                    <select class="form-select form-select-sm"
                        name="standard_id"
                        @if ($useCascade)
                            data-cascade-target="standards"
                            data-placeholder="All Standards"
                            data-keep-value="{{ request('standard_id') }}"
                        @endif>
                        <option value="">All Standards</option>
                        @unless ($useCascade)
                            @foreach ($standards as $s)
                                <option value="{{ $s->id }}" @selected((string) request('standard_id') === (string) $s->id)>
                                    {{ $s->name }}
                                </option>
                            @endforeach
                        @endunless
                    </select>
                </div>
            @endif

            @if ($showBatch)
                <div class="col-md-6 col-lg-3">
                    <label class="form-label fw-semibold">Batch</label>
                    <select class="form-select form-select-sm"
                        name="batch_id"
                        @if ($useCascade)
                            data-cascade-target="batches"
                            data-placeholder="All Batches"
                            data-keep-value="{{ request('batch_id') }}"
                        @endif>
                        <option value="">All Batches</option>
                        @unless ($useCascade)
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}"
                                    data-branch-id="{{ $batch->branch_id }}"
                                    @selected((string) request('batch_id') === (string) $batch->id)>
                                    {{ $batch->name }}
                                </option>
                            @endforeach
                        @endunless
                    </select>
                </div>
            @endif

            @if ($showStatus && $statuses !== [])
                <div class="col-md-6 col-lg-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select class="form-select form-select-sm" name="status">
                        <option value="">All Status</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected((string) request('status') === (string) $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{ $slot }}

            <div class="col-md-6 col-lg-2">
                <button type="submit" class="btn btn-sm w-100 admin-btn-primary">
                    <i class="bx bx-filter-alt me-1"></i> Filter
                </button>
            </div>
            @if (request()->hasAny(['institute_id', 'branch_id', 'standard_id', 'batch_id', 'status', 'q', 'type', 'category_id', 'payment_method', 'start_date', 'end_date', 'follow_up_date', 'from', 'to', 'demo_status', 'assignee_id', 'date']))
                <div class="col-md-6 col-lg-2">
                    <a href="{{ $action }}" class="btn btn-sm w-100 btn-outline-secondary">Clear</a>
                </div>
            @endif
        </div>
    </form>
</div>
