@php
    $instituteId = $instituteId ?? null;
    $institutes = $institutes ?? collect();
    $label = $label ?? 'Institute';
    $colClass = $colClass ?? 'col-md-6';
    $resolvedId = old('institute_id', $formScope->defaultInstituteId($instituteId));
@endphp

@if ($formScope->showInstituteField())
    <div class="{{ $colClass }}">
        <label class="form-label fw-semibold" style="color: var(--iv-primary);">
            {{ $label }} <span class="text-danger">*</span>
        </label>
        <select class="form-select" name="institute_id" data-cascade-source required>
            <option value="">Select institute</option>
            @foreach ($institutes as $inst)
                <option value="{{ $inst->id }}" @selected((int) $resolvedId === (int) $inst->id)>{{ $inst->name }}</option>
            @endforeach
        </select>
    </div>
@else
    <input type="hidden" name="institute_id" data-cascade-source value="{{ $resolvedId }}">
@endif
