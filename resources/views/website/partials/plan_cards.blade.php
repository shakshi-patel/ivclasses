@php
    $plans = $plans ?? collect();
    $cardMode = $cardMode ?? 'link';
    $selectedPlanId = (int) ($selectedPlanId ?? 0);
    $featured = $plans->firstWhere('code', 'growth')
        ?? ($plans->count() >= 2 ? $plans->values()->get((int) floor(($plans->count() - 1) / 2)) : null);
    $featuredId = (int) ($featured->id ?? 0);
@endphp

@if ($plans->isEmpty())
    <div class="iv-plans-empty">
        <p>Plans are not published yet.</p>
        <a href="{{ route('book-demo') }}" class="btn btn-primary">Book a free demo</a>
    </div>
@else
    <div class="iv-plans">
        @foreach ($plans as $plan)
            @php
                $isFeatured = (int) $plan->id === $featuredId && $plans->count() > 1;
                $isSelected = (int) $plan->id === $selectedPlanId;
                $points = collect($plan->benefits_json ?? [])
                    ->map(fn ($point) => trim((string) $point))
                    ->filter()
                    ->values();
                if ($points->isEmpty()) {
                    $points = collect([
                        $plan->max_branches
                            ? $plan->max_branches.' '.($plan->max_branches === 1 ? 'branch' : 'branches')
                            : 'Unlimited branches',
                        $plan->max_students
                            ? 'Up to '.number_format((int) $plan->max_students).' students'
                            : 'Unlimited students',
                        $plan->max_staff
                            ? 'Up to '.$plan->max_staff.' staff'
                            : 'Unlimited staff',
                    ]);
                    if ((int) $plan->trial_days > 0) {
                        $points->push($plan->trial_days.'-day free trial');
                    }
                }
                $currency = strtoupper((string) ($plan->currency ?: 'INR'));
                $symbol = $currency === 'INR' ? '₹' : ($currency === 'USD' ? '$' : $currency.' ');
                $monthly = (float) $plan->price_monthly;
            @endphp
            <article class="iv-plan {{ $isFeatured ? 'is-featured' : '' }} {{ $isSelected ? 'is-selected' : '' }}">
                <div class="iv-plan-top">
                    @if ($isFeatured)
                        <span class="iv-plan-ribbon">Most chosen</span>
                    @endif
                    <h3 class="iv-plan-name">{{ $plan->name }}</h3>
                    @if ($plan->description)
                        <p class="iv-plan-for">{{ $plan->description }}</p>
                    @endif
                    <p class="iv-plan-price">
                        @if ($monthly <= 0)
                            Free
                        @else
                            {{ $symbol }}{{ number_format($monthly, 0) }}<small>/mo</small>
                        @endif
                    </p>
                    @if ((int) $plan->trial_days > 0)
                        <p class="iv-plan-trial">{{ $plan->trial_days }}-day trial · no payment now</p>
                    @endif
                </div>
                <ul class="iv-plan-points">
                    @foreach ($points->take(6) as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ul>
                @if ($cardMode === 'pick')
                    <button type="button"
                        class="btn iv-plan-btn iv-plan-pick {{ $isSelected ? 'is-selected' : '' }}"
                        data-plan-id="{{ $plan->id }}"
                        data-plan-name="{{ $plan->name }}"
                        data-trial-days="{{ (int) $plan->trial_days }}"
                        data-max-branches="{{ $plan->max_branches === null ? '' : (int) $plan->max_branches }}">
                        Start free trial
                    </button>
                @else
                    <a href="{{ route('register-institute', ['plan' => $plan->code]) }}" class="btn iv-plan-btn">
                        Start free trial
                    </a>
                @endif
            </article>
        @endforeach
    </div>
@endif
