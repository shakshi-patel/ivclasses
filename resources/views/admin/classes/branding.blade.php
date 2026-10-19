@extends('admin.layouts.layout')

@section('content')
    @php
        $settings = $institute->settings_json ?? [];
        $feesTerms = old('fees_receipt_terms', $institute->feesReceiptTerms());
        if (! is_array($feesTerms) || count($feesTerms) === 0) {
            $feesTerms = [''];
        }
        $themeColors = $institute->themeColors();
    @endphp

    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-buildings"></i>
                    Institute Setting
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Institute Setting</li>
                    </ol>
                </nav>

                @if ($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="master-stu-form-wrap mt-3">
                    <div class="master-stu-form-card">
                        <div class="stu-form-head">Institute profile &amp; branding</div>
                        <div class="stu-form-body">
                            <form method="POST" action="{{ route('admin.classes.update') }}" enctype="multipart/form-data" id="brandingForm">
                                @csrf
                                @method('PUT')

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">UID Number</label>
                                        <input type="text" class="form-control" value="{{ $institute->code }}" readonly>
                                        <div class="form-text">Auto-generated unique institute ID.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Institute Name <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            value="{{ old('name', $institute->name) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tagline</label>
                                        <input type="text" class="form-control" name="tagline"
                                            value="{{ old('tagline', $settings['tagline'] ?? '') }}"
                                            placeholder="e.g. Best coaching in Mehsana">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email <span class="req">*</span></label>
                                        <input type="email" class="form-control" name="email"
                                            value="{{ old('email', $institute->email) }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Mobile Number</label>
                                        <input type="text" class="form-control" name="phone" maxlength="15" minlength="10" pattern="[0-9]{10,15}" inputmode="numeric" data-phone-input
                                            value="{{ old('phone', $institute->phone) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">GSTIN</label>
                                        <input type="text" class="form-control" name="gstin"
                                            value="{{ old('gstin', $institute->gstin) }}" placeholder="24AAAAA0000A1Z5">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">City</label>
                                        <input type="text" class="form-control" name="city"
                                            value="{{ old('city', $institute->city) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">State</label>
                                        <input type="text" class="form-control" name="state"
                                            value="{{ old('state', $institute->state) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Pincode</label>
                                        <input type="text" class="form-control" name="pincode"
                                            value="{{ old('pincode', $institute->pincode) }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Full Address</label>
                                        <textarea class="form-control" name="address" rows="3">{{ old('address', $institute->address) }}</textarea>
                                    </div>

                                    <div class="col-12">
                                        <div class="branding-section-head">
                                            <div>
                                                <h6 class="mb-0">Terms &amp; Conditions For Fees Receipt</h6>
                                                <p class="mb-0 small text-muted">Add receipt terms shown on fee receipts. Use + to add more.</p>
                                            </div>
                                            <button type="button" class="btn btn-sm admin-btn-primary" id="addFeesTermBtn">
                                                <i class="bx bx-plus"></i>
                                            </button>
                                        </div>
                                        <div class="fees-terms-list" id="feesTermsList">
                                            @foreach ($feesTerms as $term)
                                                <div class="fees-term-row">
                                                    <div class="fees-term-index">{{ $loop->iteration }}</div>
                                                    <input type="text" class="form-control" name="fees_receipt_terms[]"
                                                        value="{{ $term }}" placeholder="Enter term / condition title">
                                                    <button type="button" class="btn btn-sm btn-outline-danger fees-term-remove" title="Remove"
                                                        @if ($loop->count <= 1) disabled @endif>
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="branding-section-head mb-2">
                                            <div>
                                                <h6 class="mb-0">Branding assets</h6>
                                                <p class="mb-0 small text-muted">Upload clear PNG/JPG images. Drag &amp; drop or click to replace.</p>
                                            </div>
                                        </div>
                                        <div class="row g-3 branding-dropzone-row">
                                            @php
                                                $assetCards = [
                                                    [
                                                        'key' => 'logo',
                                                        'label' => 'Institute Logo',
                                                        'required' => true,
                                                        'usage' => 'Used in admin sidebar, print headers, and fee receipts.',
                                                        'current' => $institute->logoUrl(),
                                                        'hint' => 'Square or landscape · Max 2MB',
                                                    ],
                                                    [
                                                        'key' => 'signature',
                                                        'label' => 'Authorized Signature',
                                                        'required' => true,
                                                        'usage' => 'Printed on fee receipts as authorized signatory.',
                                                        'current' => $institute->signatureUrl(),
                                                        'hint' => 'Transparent PNG preferred · Max 2MB',
                                                    ],
                                                    [
                                                        'key' => 'stamp',
                                                        'label' => 'Office Stamp',
                                                        'required' => true,
                                                        'usage' => 'Printed on fee receipts as official office stamp.',
                                                        'current' => $institute->stampUrl(),
                                                        'hint' => 'Round/square stamp · Max 2MB',
                                                    ],
                                                ];
                                            @endphp
                                            @foreach ($assetCards as $card)
                                                <div class="col-md-4">
                                                    <div class="branding-asset-card">
                                                        <div class="branding-asset-card__label">
                                                            {{ $card['label'] }}
                                                            @if ($card['required']) <span class="req">*</span> @endif
                                                        </div>
                                                        <div class="branding-dropzone dropzone" id="dz-{{ $card['key'] }}"
                                                            data-input="{{ $card['key'] }}"
                                                            data-current="{{ $card['current'] ?? '' }}"
                                                            data-label="{{ $card['label'] }}">
                                                            <div class="dz-message needsclick mb-0">
                                                                <i class="bx bx-cloud-upload branding-dropzone__icon"></i>
                                                                <div class="branding-dropzone__title">Drop image here</div>
                                                                <div class="branding-dropzone__sub">or click to browse</div>
                                                            </div>
                                                        </div>
                                                        <input type="file" class="d-none branding-file-input" name="{{ $card['key'] }}"
                                                            id="input-{{ $card['key'] }}" accept="image/*">
                                                        @if ($card['current'])
                                                            <div class="branding-current" id="current-{{ $card['key'] }}">
                                                                <div class="branding-current__badge">Current</div>
                                                                <img src="{{ $card['current'] }}" alt="{{ $card['label'] }}">
                                                            </div>
                                                        @endif
                                                        <div class="branding-asset-card__usage">
                                                            <strong>Usage:</strong> {{ $card['usage'] }}
                                                            <span class="d-block mt-1 text-muted">{{ $card['hint'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <div class="master-stu-form-actions mt-4">
                                    <button type="submit" class="btn admin-btn-primary px-4">
                                        <i class="bx bx-save me-1"></i> Save settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="master-stu-form-card mt-4">
                        <div class="stu-form-head d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span><i class="bx bx-palette me-1"></i> Admin Panel Theme</span>
                        </div>
                        <div class="stu-form-body">
                            <div class="alert admin-theme-note d-flex align-items-start mb-4" role="alert">
                                <i class="bx bx-info-circle me-2 fs-5"></i>
                                <div>
                                    Pick the colors you want for your institute's admin panel. Saving here applies
                                    them across your admin panel immediately.
                                </div>
                            </div>

                            <form method="POST" action="{{ route('admin.classes.theme.update') }}" id="themeSettingsForm">
                                @csrf
                                @method('PUT')
                                <div class="branding-section-head">
                                    <div>
                                        <h6 class="mb-0">Brand colors</h6>
                                        <p class="mb-0 small text-muted">Buttons, active menu, links and highlights across your admin panel.</p>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label fw-semibold">Primary</label>
                                        <div class="iv-color-field">
                                            <input type="color" class="iv-color-swatch" value="{{ $themeColors['primary'] }}"
                                                data-theme-var="--iv-primary">
                                            <input type="text" class="form-control iv-color-hex" name="theme_primary"
                                                value="{{ old('theme_primary', $themeColors['primary']) }}"
                                                data-theme-var="--iv-primary" maxlength="7" required>
                                        </div>
                                        <div class="form-text">Buttons, links, active menu</div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label fw-semibold">Primary (dark)</label>
                                        <div class="iv-color-field">
                                            <input type="color" class="iv-color-swatch" value="{{ $themeColors['primary_dark'] }}"
                                                data-theme-var="--iv-primary-dark">
                                            <input type="text" class="form-control iv-color-hex" name="theme_primary_dark"
                                                value="{{ old('theme_primary_dark', $themeColors['primary_dark']) }}"
                                                data-theme-var="--iv-primary-dark" maxlength="7" required>
                                        </div>
                                        <div class="form-text">Hover states, sidebar</div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label fw-semibold">Accent</label>
                                        <div class="iv-color-field">
                                            <input type="color" class="iv-color-swatch" value="{{ $themeColors['secondary'] }}"
                                                data-theme-var="--iv-secondary">
                                            <input type="text" class="form-control iv-color-hex" name="theme_secondary"
                                                value="{{ old('theme_secondary', $themeColors['secondary']) }}"
                                                data-theme-var="--iv-secondary" maxlength="7" required>
                                        </div>
                                        <div class="form-text">Badges, highlights</div>
                                    </div>
                                    <div class="col-md-3 col-sm-6">
                                        <label class="form-label fw-semibold">Accent (dark)</label>
                                        <div class="iv-color-field">
                                            <input type="color" class="iv-color-swatch" value="{{ $themeColors['secondary_dark'] }}"
                                                data-theme-var="--iv-secondary-dark">
                                            <input type="text" class="form-control iv-color-hex" name="theme_secondary_dark"
                                                value="{{ old('theme_secondary_dark', $themeColors['secondary_dark']) }}"
                                                data-theme-var="--iv-secondary-dark" maxlength="7" required>
                                        </div>
                                        <div class="form-text">Accent hover / borders</div>
                                    </div>
                                </div>

                                <div class="branding-section-head">
                                    <div>
                                        <h6 class="mb-0">Quick presets</h6>
                                        <p class="mb-0 small text-muted">Click a palette to apply it to the fields above.</p>
                                    </div>
                                </div>
                                <div class="iv-preset-row mb-4">
                                    <button type="button" class="iv-preset-swatch" data-preset="teal-gold"
                                        style="background: linear-gradient(135deg,#0f5c4d 50%,#d9a441 50%);">
                                        <span>Teal &amp; Gold</span>
                                    </button>
                                    <button type="button" class="iv-preset-swatch" data-preset="royal-blue"
                                        style="background: linear-gradient(135deg,#1d4ed8 50%,#f59e0b 50%);">
                                        <span>Royal Blue</span>
                                    </button>
                                    <button type="button" class="iv-preset-swatch" data-preset="crimson"
                                        style="background: linear-gradient(135deg,#9d2235 50%,#d4af37 50%);">
                                        <span>Crimson &amp; Gold</span>
                                    </button>
                                    <button type="button" class="iv-preset-swatch" data-preset="slate"
                                        style="background: linear-gradient(135deg,#334155 50%,#64748b 50%);">
                                        <span>Slate</span>
                                    </button>
                                    <button type="button" class="iv-preset-swatch" data-preset="emerald"
                                        style="background: linear-gradient(135deg,#047857 50%,#34d399 50%);">
                                        <span>Emerald</span>
                                    </button>
                                    <button type="button" class="iv-preset-swatch" data-preset="violet"
                                        style="background: linear-gradient(135deg,#5b21b6 50%,#a78bfa 50%);">
                                        <span>Violet</span>
                                    </button>
                                </div>

                                <div class="branding-section-head">
                                    <div>
                                        <h6 class="mb-0">Live preview</h6>
                                        <p class="mb-0 small text-muted">How your sidebar, buttons and badges will look.</p>
                                    </div>
                                </div>
                                <div class="iv-theme-preview mb-4" id="ivThemePreview">
                                    <div class="iv-tp-sidebar">
                                        <div class="iv-tp-brand">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($institute->name ?? 'IV', 0, 2)) }}</div>
                                        <div class="iv-tp-menu-item active"><i class="bx bx-grid-alt"></i> Dashboard</div>
                                        <div class="iv-tp-menu-item"><i class="bx bx-group"></i> Students</div>
                                        <div class="iv-tp-menu-item"><i class="bx bx-calendar"></i> Batches</div>
                                    </div>
                                    <div class="iv-tp-main">
                                        <div class="iv-tp-topbar">
                                            <span class="fw-semibold">Dashboard</span>
                                            <span class="iv-tp-avatar"></span>
                                        </div>
                                        <div class="iv-tp-card">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <div class="iv-tp-label">Total fees collected</div>
                                                    <div class="iv-tp-value">₹ 1,24,500</div>
                                                </div>
                                                <span class="iv-tp-badge iv-tp-badge-accent">+12%</span>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 mt-3">
                                                <button type="button" class="iv-tp-btn iv-tp-btn-primary">Primary action</button>
                                                <button type="button" class="iv-tp-btn iv-tp-btn-outline">Secondary</button>
                                                <span class="iv-tp-badge iv-tp-badge-success">Success</span>
                                                <span class="iv-tp-badge iv-tp-badge-warning">Warning</span>
                                                <span class="iv-tp-badge iv-tp-badge-error">Error</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <button type="button" class="btn btn-outline-secondary" id="resetThemeBtn">
                                        <i class="bx bx-reset me-1"></i> Reset to default
                                    </button>
                                    <button type="submit" class="btn admin-btn-primary px-4">
                                        <i class="bx bx-save me-1"></i> Save theme settings
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .branding-section-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
        padding: 0.85rem 1rem;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(var(--iv-primary-rgb), 0.08), rgba(var(--iv-secondary-rgb), 0.1));
        border: 1px solid rgba(var(--iv-primary-rgb), 0.1);
    }
    .branding-section-head h6 {
        color: var(--iv-primary-dark);
        font-weight: 750;
    }
    .fees-terms-list {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }
    .fees-term-row {
        display: grid;
        grid-template-columns: 38px 1fr 42px;
        gap: 0.55rem;
        align-items: center;
    }
    .fees-term-index {
        height: 38px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(var(--iv-primary-rgb), 0.1);
        color: var(--iv-primary);
        font-weight: 750;
        font-size: 0.82rem;
    }
    .branding-asset-card {
        height: 100%;
        border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
        border-radius: 14px;
        padding: 0.9rem;
        background: #fff;
        box-shadow: 0 6px 18px rgba(15, 40, 35, 0.04);
    }
    .branding-asset-card__label {
        font-weight: 750;
        color: var(--iv-primary-dark);
        margin-bottom: 0.55rem;
        font-size: 0.9rem;
    }
    .branding-dropzone.dropzone {
        min-height: 140px;
        border: 1.5px dashed rgba(var(--iv-primary-rgb), 0.35);
        border-radius: 12px;
        background: linear-gradient(180deg, rgba(var(--iv-primary-rgb), 0.04), #fff);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: border-color .2s, background .2s, box-shadow .2s;
        padding: 1rem 0.75rem;
    }
    .branding-dropzone.dropzone:hover,
    .branding-dropzone.dropzone.dz-drag-hover {
        border-color: var(--iv-primary);
        background: rgba(var(--iv-primary-rgb), 0.07);
        box-shadow: 0 0 0 3px rgba(var(--iv-primary-rgb), 0.1);
    }
    .branding-dropzone .dz-message {
        margin: 0 !important;
        text-align: center;
    }
    .branding-dropzone__icon {
        font-size: 1.7rem;
        color: var(--iv-primary);
        display: block;
        margin-bottom: 0.25rem;
    }
    .branding-dropzone__title {
        font-weight: 700;
        color: var(--iv-primary-dark);
        font-size: 0.86rem;
    }
    .branding-dropzone__sub {
        font-size: 0.75rem;
        color: var(--iv-text-secondary);
    }
    .branding-dropzone.dropzone .dz-preview {
        margin: 0.35rem;
        min-height: auto;
    }
    .branding-dropzone.dropzone .dz-preview .dz-image {
        width: 88px;
        height: 88px;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
    }
    .branding-dropzone.dropzone .dz-preview .dz-details {
        padding: 0.35rem 0 0;
    }
    .branding-current {
        margin-top: 0.7rem;
        border-radius: 10px;
        border: 1px solid rgba(var(--iv-primary-rgb), 0.12);
        background: rgba(var(--iv-primary-rgb), 0.03);
        padding: 0.55rem;
        text-align: center;
        position: relative;
    }
    .branding-current__badge {
        position: absolute;
        top: 6px;
        left: 6px;
        font-size: 0.65rem;
        font-weight: 750;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        background: var(--iv-primary);
        color: #fff;
        border-radius: 999px;
        padding: 0.15rem 0.45rem;
    }
    .branding-current img {
        max-height: 72px;
        max-width: 100%;
        object-fit: contain;
        border-radius: 6px;
    }
    .branding-asset-card__usage {
        margin-top: 0.7rem;
        font-size: 0.76rem;
        color: var(--iv-text-secondary);
        line-height: 1.4;
    }
    .branding-asset-card__usage strong {
        color: var(--iv-primary-dark);
    }
    @media (max-width: 767.98px) {
        .fees-term-row {
            grid-template-columns: 32px 1fr 38px;
        }
    }

    .admin-theme-note {
        background: rgba(var(--iv-info-rgb), 0.08);
        border: 1px solid rgba(var(--iv-info-rgb), 0.22);
        color: var(--iv-primary-dark);
        border-radius: 10px;
    }
    .iv-color-field {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .iv-color-swatch {
        width: 42px;
        height: 38px;
        padding: 2px;
        border: 1px solid var(--iv-border);
        border-radius: 8px;
        cursor: pointer;
        flex: 0 0 auto;
    }
    .iv-color-hex {
        text-transform: lowercase;
        font-family: 'Courier New', monospace;
    }
    .iv-preset-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.9rem;
    }
    .iv-preset-swatch {
        border: 2px solid var(--iv-border);
        border-radius: 10px;
        width: 108px;
        height: 64px;
        padding: 0;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: transform .12s ease, border-color .12s ease;
    }
    .iv-preset-swatch:hover {
        transform: translateY(-2px);
        border-color: var(--iv-primary);
    }
    .iv-preset-swatch span {
        position: absolute;
        inset: auto 0 0 0;
        background: rgba(0, 0, 0, 0.45);
        color: #fff;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 3px 6px;
        text-align: center;
    }
    /* Live preview mock — scoped variables so it doesn't affect the real admin UI */
    .iv-theme-preview {
        --tp-primary: #0f5c4d;
        --tp-primary-dark: #08483d;
        --tp-secondary: #d9a441;
        --tp-secondary-dark: #b7862a;
        --tp-success: #2e8b68;
        --tp-warning: #e5a72f;
        --tp-error: #d9534f;
        display: flex;
        border: 1px solid var(--iv-border);
        border-radius: 12px;
        overflow: hidden;
        min-height: 230px;
    }
    .iv-tp-sidebar {
        width: 170px;
        flex: 0 0 auto;
        background: var(--tp-primary-dark);
        color: #fff;
        padding: 1rem 0.75rem;
    }
    .iv-tp-brand {
        font-weight: 700;
        letter-spacing: 0.04em;
        background: rgba(255, 255, 255, 0.12);
        border-radius: 6px;
        padding: 0.4rem 0.6rem;
        margin-bottom: 0.9rem;
        display: inline-block;
    }
    .iv-tp-menu-item {
        font-size: 0.82rem;
        padding: 0.45rem 0.6rem;
        border-radius: 6px;
        margin-bottom: 0.3rem;
        color: rgba(255, 255, 255, 0.78);
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
    .iv-tp-menu-item.active {
        background: var(--tp-primary);
        color: #fff;
        font-weight: 600;
    }
    .iv-tp-main {
        flex: 1 1 auto;
        background: #f8f9fb;
        padding: 1rem 1.1rem;
    }
    .iv-tp-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 0.75rem;
        margin-bottom: 0.9rem;
        border-bottom: 1px solid var(--iv-border);
    }
    .iv-tp-avatar {
        width: 26px;
        height: 26px;
        border-radius: 50%;
        background: var(--tp-secondary);
        display: inline-block;
    }
    .iv-tp-card {
        background: #fff;
        border: 1px solid var(--iv-border);
        border-radius: 10px;
        padding: 0.9rem 1rem;
    }
    .iv-tp-label {
        font-size: 0.78rem;
        color: var(--iv-text-secondary);
    }
    .iv-tp-value {
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--tp-primary-dark);
    }
    .iv-tp-btn {
        border: none;
        border-radius: 7px;
        padding: 0.4rem 0.9rem;
        font-size: 0.82rem;
        font-weight: 600;
        cursor: default;
    }
    .iv-tp-btn-primary {
        background: var(--tp-primary);
        color: #fff;
    }
    .iv-tp-btn-outline {
        background: transparent;
        border: 1px solid var(--tp-primary);
        color: var(--tp-primary);
    }
    .iv-tp-badge {
        border-radius: 20px;
        padding: 0.25rem 0.65rem;
        font-size: 0.74rem;
        font-weight: 600;
        align-self: center;
    }
    .iv-tp-badge-accent {
        background: rgba(217, 164, 65, 0.18);
        color: var(--tp-secondary-dark);
    }
    .iv-tp-badge-success {
        background: rgba(46, 139, 104, 0.15);
        color: var(--tp-success);
    }
    .iv-tp-badge-warning {
        background: rgba(229, 167, 47, 0.18);
        color: var(--tp-warning);
    }
    .iv-tp-badge-error {
        background: rgba(217, 83, 79, 0.15);
        color: var(--tp-error);
    }
    @media (max-width: 575px) {
        .iv-theme-preview {
            flex-direction: column;
        }
        .iv-tp-sidebar {
            width: 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.getElementById('brandingForm');
    if (!form || typeof Dropzone === 'undefined') return;

    Dropzone.autoDiscover = false;

    const previewTemplate = `<div class="dz-preview dz-file-preview">
        <div class="dz-details">
            <div class="dz-thumbnail">
                <img data-dz-thumbnail>
                <span class="dz-nopreview">No preview</span>
                <div class="dz-success-mark"></div>
                <div class="dz-error-mark"></div>
                <div class="dz-error-message"><span data-dz-errormessage></span></div>
            </div>
            <div class="dz-filename"><span data-dz-name></span></div>
            <div class="dz-size" data-dz-size></div>
        </div>
        <a class="dz-remove" href="javascript:undefined;" data-dz-remove>Remove</a>
    </div>`;

    function syncInput(input, file) {
        const dt = new DataTransfer();
        if (file) dt.items.add(file);
        input.files = dt.files;
    }

    document.querySelectorAll('.branding-dropzone').forEach(function (el) {
        const inputName = el.dataset.input;
        const input = document.getElementById('input-' + inputName);
        const currentWrap = document.getElementById('current-' + inputName);
        if (!input) return;

        const dz = new Dropzone(el, {
            url: '/',
            autoProcessQueue: false,
            maxFiles: 1,
            maxFilesize: 2,
            acceptedFiles: 'image/*',
            addRemoveLinks: true,
            previewTemplate: previewTemplate,
            clickable: true,
            createImageThumbnails: true,
            dictDefaultMessage: ''
        });

        dz.on('addedfile', function (file) {
            if (dz.files.length > 1) {
                dz.removeFile(dz.files[0]);
            }
            syncInput(input, file);
            if (currentWrap) currentWrap.style.display = 'none';
            const msg = el.querySelector('.dz-message');
            if (msg) msg.style.display = 'none';
        });

        dz.on('removedfile', function () {
            syncInput(input, null);
            if (currentWrap) currentWrap.style.display = '';
            const msg = el.querySelector('.dz-message');
            if (msg && dz.files.length === 0) msg.style.display = '';
        });

        dz.on('error', function (file, message) {
            if (file && file.previewElement) {
                dz.removeFile(file);
            }
            if (window.AdminAjax && AdminAjax.toast) {
                AdminAjax.toast(typeof message === 'string' ? message : 'Invalid image file.', 'error');
            } else {
                alert(typeof message === 'string' ? message : 'Invalid image file.');
            }
        });
    });

    const termsList = document.getElementById('feesTermsList');
    const addTermBtn = document.getElementById('addFeesTermBtn');

    function reindexTerms() {
        if (!termsList) return;
        const rows = termsList.querySelectorAll('.fees-term-row');
        rows.forEach(function (row, i) {
            const idx = row.querySelector('.fees-term-index');
            if (idx) idx.textContent = String(i + 1);
            const removeBtn = row.querySelector('.fees-term-remove');
            if (removeBtn) removeBtn.disabled = rows.length <= 1;
        });
    }

    function makeTermRow(value) {
        const row = document.createElement('div');
        row.className = 'fees-term-row';
        row.innerHTML = `
            <div class="fees-term-index">1</div>
            <input type="text" class="form-control" name="fees_receipt_terms[]" value="${(value || '').replace(/"/g, '&quot;')}" placeholder="Enter term / condition title">
            <button type="button" class="btn btn-sm btn-outline-danger fees-term-remove" title="Remove">
                <i class="bx bx-trash"></i>
            </button>`;
        return row;
    }

    addTermBtn?.addEventListener('click', function () {
        termsList.appendChild(makeTermRow(''));
        reindexTerms();
        termsList.lastElementChild?.querySelector('input')?.focus();
    });

    termsList?.addEventListener('click', function (e) {
        const btn = e.target.closest('.fees-term-remove');
        if (!btn || btn.disabled) return;
        btn.closest('.fees-term-row')?.remove();
        if (!termsList.querySelector('.fees-term-row')) {
            termsList.appendChild(makeTermRow(''));
        }
        reindexTerms();
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = this.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
        try {
            await AdminAjax.submitForm(this, {
                redirectUrl: @json(route('admin.classes'))
            });
        } catch (err) {
            if (btn) btn.disabled = false;
        }
    });
})();

(function () {
    const form = document.getElementById('themeSettingsForm');
    if (!form) return;

    const preview = document.getElementById('ivThemePreview');

    const varToPreviewProp = {
        '--iv-primary': '--tp-primary',
        '--iv-primary-dark': '--tp-primary-dark',
        '--iv-secondary': '--tp-secondary',
        '--iv-secondary-dark': '--tp-secondary-dark',
    };

    const defaults = {};
    form.querySelectorAll('[data-theme-var]').forEach(function (el) {
        if (!defaults[el.dataset.themeVar]) {
            defaults[el.dataset.themeVar] = el.value;
        }
    });

    function isValidHex(value) {
        return /^#[0-9a-fA-F]{6}$/.test(value);
    }

    function applyToPreview(themeVar, value) {
        const prop = varToPreviewProp[themeVar];
        if (prop && preview) {
            preview.style.setProperty(prop, value);
        }
    }

    function setColor(themeVar, value, skip) {
        if (!isValidHex(value)) return;
        form.querySelectorAll('[data-theme-var="' + themeVar + '"]').forEach(function (el) {
            if (el === skip) return;
            el.value = value;
        });
        applyToPreview(themeVar, value);
    }

    form.querySelectorAll('.iv-color-swatch').forEach(function (swatch) {
        swatch.addEventListener('input', function () {
            setColor(this.dataset.themeVar, this.value, this);
        });
    });

    form.querySelectorAll('.iv-color-hex').forEach(function (text) {
        text.addEventListener('input', function () {
            const value = this.value.trim();
            if (isValidHex(value)) {
                setColor(this.dataset.themeVar, value, this);
            }
        });
    });

    const presets = {
        'teal-gold': {
            '--iv-primary': '#0f5c4d',
            '--iv-primary-dark': '#08483d',
            '--iv-secondary': '#d9a441',
            '--iv-secondary-dark': '#b7862a',
        },
        'royal-blue': {
            '--iv-primary': '#1d4ed8',
            '--iv-primary-dark': '#1e3a8a',
            '--iv-secondary': '#f59e0b',
            '--iv-secondary-dark': '#b45309',
        },
        'crimson': {
            '--iv-primary': '#9d2235',
            '--iv-primary-dark': '#6b1424',
            '--iv-secondary': '#d4af37',
            '--iv-secondary-dark': '#a3821f',
        },
        'slate': {
            '--iv-primary': '#334155',
            '--iv-primary-dark': '#1e293b',
            '--iv-secondary': '#64748b',
            '--iv-secondary-dark': '#475569',
        },
        'emerald': {
            '--iv-primary': '#047857',
            '--iv-primary-dark': '#065f46',
            '--iv-secondary': '#34d399',
            '--iv-secondary-dark': '#10b981',
        },
        'violet': {
            '--iv-primary': '#5b21b6',
            '--iv-primary-dark': '#3b0764',
            '--iv-secondary': '#a78bfa',
            '--iv-secondary-dark': '#8b5cf6',
        },
    };

    form.querySelectorAll('.iv-preset-swatch').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const preset = presets[this.dataset.preset];
            if (!preset) return;
            Object.keys(preset).forEach(function (themeVar) {
                setColor(themeVar, preset[themeVar], null);
            });
        });
    });

    const resetBtn = document.getElementById('resetThemeBtn');
    resetBtn?.addEventListener('click', function () {
        Object.keys(defaults).forEach(function (themeVar) {
            setColor(themeVar, defaults[themeVar], null);
        });
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = this.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
        try {
            await AdminAjax.submitForm(this, {
                redirectUrl: @json(route('admin.classes'))
            });
        } catch (err) {
            if (btn) btn.disabled = false;
        }
    });
})();
</script>
@endpush
