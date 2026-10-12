@php
    $branchId = $branchId ?? null;
    $branches = $branches ?? collect();
    $name = $name ?? 'branch_id';
    $label = $label ?? 'Branch';
    $colClass = $colClass ?? 'col-md-6';
    $required = $required ?? true;
    $cascade = $cascade ?? true;
    $id = $id ?? null;
    $resolvedId = old($name, $formScope->defaultBranchId($branchId));
@endphp

@if ($formScope->showBranchField())
    <div class="{{ $colClass }}">
        <label class="form-label fw-semibold" style="color: var(--iv-primary);">
            {{ $label }} @if ($required)<span class="text-danger">*</span>@endif
        </label>
        <select class="form-select" name="{{ $name }}" @if ($id) id="{{ $id }}" @endif @if ($required) required @endif
            @if ($cascade) data-cascade-target="branches" data-placeholder="Select branch" @endif
            @if ($resolvedId) data-keep-value="{{ $resolvedId }}" @endif>
            <option value="">Select branch</option>
            @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" @selected((int) $resolvedId === (int) $branch->id)>{{ $branch->name }}</option>
            @endforeach
        </select>
        @isset($hint)
            <div class="form-text">{{ $hint }}</div>
        @endisset
    </div>
@else
    <input type="hidden" name="{{ $name }}" @if ($id) id="{{ $id }}" @endif value="{{ $resolvedId }}">
@endif
