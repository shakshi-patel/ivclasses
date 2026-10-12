@php
    $status = $institute->status;
    $statusClass = $status?->pillClass() ?? 'is-navy';
    $statusLabel = $status?->label() ?? '—';
    $extra = $extraModules ?? [];
    $blocked = $blockedModules ?? [];
    $effective = $effectiveModules ?? [];
    $planOnly = $planModules ?? [];
    $limits = $limits ?? [];
    $planFeature = array_values(array_diff($planOnly, \App\Support\PlanModules::CORE));
    $effFeature = array_values(array_diff($effective, \App\Support\PlanModules::CORE));
@endphp

<div class="inst-view">
    <div class="inst-view-hero">
        <div class="inst-view-logo">
            @if ($institute->logo_path)
                <img src="{{ $institute->logoUrl() }}" alt="{{ $institute->name }}">
            @else
                <span>{{ \Illuminate\Support\Str::of($institute->name)->substr(0, 2)->upper() }}</span>
            @endif
        </div>
        <div class="inst-view-hero-text">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <h5 class="mb-0">{{ $institute->name }}</h5>
                <span class="admin-status-pill {{ $statusClass }}">{{ $statusLabel }}</span>
                @if ($isTrial)
                    <span class="inst-trial-badge {{ $trialExpired ? 'is-expired' : '' }}">
                        <i class="bx bx-gift"></i>
                        {{ $trialExpired ? 'Free trial expired' : 'Free trial' }}
                    </span>
                @endif
            </div>
            <div class="inst-view-meta">
                <span><code>{{ $institute->code }}</code></span>
                <span>·</span>
                <span>{{ $institute->slug }}</span>
            </div>
        </div>
    </div>

    @if ($isTrial)
        <div class="inst-trial-banner {{ $trialExpired ? 'is-expired' : '' }}">
            <div>
                <strong>{{ $trialExpired ? 'Trial expired' : 'Currently on Free Trial' }}</strong>
                <div class="small mt-1">
                    @if ($trialEnds)
                        Trial ends: <strong>{{ $trialEnds->format('d M Y') }}</strong>
                        @if (! is_null($daysLeft))
                            ·
                            @if ($daysLeft > 0)
                                {{ $daysLeft }} day{{ $daysLeft === 1 ? '' : 's' }} left
                            @elseif ($daysLeft === 0)
                                Ends today
                            @else
                                Expired {{ abs($daysLeft) }} day{{ abs($daysLeft) === 1 ? '' : 's' }} ago
                            @endif
                        @endif
                    @else
                        Trial end date not set.
                    @endif
                </div>
            </div>
            <i class="bx bx-time-five"></i>
        </div>
    @endif

    <div class="inst-view-section">
        <div class="inst-view-section-head">
            <i class="bx bx-id-card"></i> Registration details
        </div>
        <div class="inst-view-grid">
            <div class="inst-view-item">
                <span class="k">UID / Code</span>
                <span class="v">{{ $institute->code ?: '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">UUID</span>
                <span class="v"><code class="small">{{ $institute->uuid ?: '—' }}</code></span>
            </div>
            <div class="inst-view-item">
                <span class="k">Email</span>
                <span class="v">{{ $institute->email ?: '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">Phone</span>
                <span class="v">{{ $institute->phone ?: '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">City</span>
                <span class="v">{{ $institute->city ?: '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">State</span>
                <span class="v">{{ $institute->state ?: '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">Pincode</span>
                <span class="v">{{ $institute->pincode ?: '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">GSTIN</span>
                <span class="v">{{ $institute->gstin ?: '—' }}</span>
            </div>
            <div class="inst-view-item inst-view-item-full">
                <span class="k">Address</span>
                <span class="v">{{ $institute->address ?: '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">Timezone</span>
                <span class="v">{{ $institute->timezone ?: '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">Registered</span>
                <span class="v">{{ optional($institute->created_at)->format('d M Y H:i') ?? '—' }}</span>
            </div>
        </div>
    </div>

    <div class="inst-view-section">
        <div class="inst-view-section-head">
            <i class="bx bx-user-circle"></i> Institute admin
        </div>
        <div class="inst-view-grid">
            <div class="inst-view-item">
                <span class="k">Name</span>
                <span class="v">{{ $admin?->name ?: '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">Login email</span>
                <span class="v">{{ $admin?->email ?: $institute->email ?: '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">Phone</span>
                <span class="v">{{ $admin?->phone ?: $institute->phone ?: '—' }}</span>
            </div>
        </div>
    </div>

    <div class="inst-view-section">
        <div class="inst-view-section-head">
            <i class="bx bx-purchase-tag-alt"></i> Plan &amp; subscription
        </div>
        <div class="inst-view-grid">
            <div class="inst-view-item">
                <span class="k">Plan</span>
                <span class="v">{{ $subscription?->plan?->name ?? '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">Subscription</span>
                <span class="v">{{ $subscription?->status?->value ? ucfirst($subscription->status->value) : '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">Billing cycle</span>
                <span class="v">{{ $subscription?->billing_cycle ? ucfirst($subscription->billing_cycle) : '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">Valid until</span>
                <span class="v">{{ optional($subscription?->ends_at)->format('d M Y') ?? '—' }}</span>
            </div>
            <div class="inst-view-item">
                <span class="k">Branches limit</span>
                <span class="v">
                    {{ $limits['max_branches'] ?? '∞' }}
                    @if (! empty($limits['max_branches_override']))
                        <span class="text-muted small">(override; plan {{ $limits['plan_max_branches'] ?? '∞' }})</span>
                    @endif
                </span>
            </div>
            <div class="inst-view-item">
                <span class="k">Students limit</span>
                <span class="v">
                    {{ $limits['max_students'] ?? '∞' }}
                    @if (! empty($limits['max_students_override']))
                        <span class="text-muted small">(override; plan {{ $limits['plan_max_students'] ?? '∞' }})</span>
                    @endif
                </span>
            </div>
            <div class="inst-view-item">
                <span class="k">Staff limit</span>
                <span class="v">
                    {{ $limits['max_staff'] ?? '∞' }}
                    @if (! empty($limits['max_staff_override']))
                        <span class="text-muted small">(override; plan {{ $limits['plan_max_staff'] ?? '∞' }})</span>
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div class="inst-view-section">
        <div class="inst-view-section-head">
            <i class="bx bx-slider-alt"></i> Modules (effective)
        </div>
        <div class="inst-view-grid">
            <div class="inst-view-item inst-view-item-full">
                <span class="k">From plan (+ core)</span>
                <span class="v">
                    {{ $planFeature ? implode(', ', \App\Support\PlanModules::labelsFor($planFeature)) : 'Core only' }}
                </span>
            </div>
            <div class="inst-view-item">
                <span class="k">Extra (gifted)</span>
                <span class="v text-success">
                    {{ $extra ? implode(', ', \App\Support\PlanModules::labelsFor($extra)) : '—' }}
                </span>
            </div>
            <div class="inst-view-item">
                <span class="k">Blocked</span>
                <span class="v text-danger">
                    {{ $blocked ? implode(', ', \App\Support\PlanModules::labelsFor($blocked)) : '—' }}
                </span>
            </div>
            <div class="inst-view-item inst-view-item-full">
                <span class="k">Effective access</span>
                <span class="v">
                    {{ $effFeature ? implode(', ', \App\Support\PlanModules::labelsFor($effFeature)) : 'Core only' }}
                </span>
            </div>
        </div>
    </div>
</div>
