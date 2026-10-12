@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-credit-card"></i>
                    Billing &amp; Subscription
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Billing</li>
                    </ol>
                </nav>

                @if (session('success'))
                    <div class="alert alert-success d-flex align-items-center mt-4 mb-0" role="alert">
                        <i class="bx bx-check-circle me-2 fs-5"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger d-flex align-items-center mt-4 mb-0" role="alert">
                        <i class="bx bx-error-circle me-2 fs-5"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @if ($needsBilling)
                    <div class="alert alert-warning d-flex align-items-center mt-4 mb-0" role="alert">
                        <i class="bx bx-error me-2 fs-5"></i>
                        <div>Your trial or subscription has expired. Choose a plan below to continue using the CRM.</div>
                    </div>
                @endif

                @if ($isBypassMode)
                    <div class="alert alert-info d-flex align-items-center mt-4 mb-0" role="alert">
                        <i class="bx bx-info-circle me-2 fs-5"></i>
                        <div>
                            <strong>Development mode:</strong> payments are bypassed automatically.
                            Set <code>BILLING_DRIVER=razorpay</code> or <code>stripe</code> in <code>.env</code> when your gateway accounts are ready.
                        </div>
                    </div>
                @endif

                <div class="row mt-4 g-4">
                    <div class="col-lg-5">
                        <div class="admin-detail-block h-100">
                            <h5 class="mb-3">Current plan</h5>
                            <p class="mb-1"><strong>Institute:</strong> {{ $institute->name }}</p>
                            <p class="mb-1"><strong>Status:</strong> {{ ucfirst($institute->status->value) }}</p>
                            @if ($subscription)
                                <p class="mb-1"><strong>Plan:</strong> {{ $subscription->plan?->name ?? '—' }}</p>
                                <p class="mb-1"><strong>Billing cycle:</strong> {{ ucfirst($subscription->billing_cycle) }}</p>
                                <p class="mb-1"><strong>Valid until:</strong>
                                    {{ $subscription->ends_at?->format('d M Y') ?? '—' }}</p>
                            @elseif ($institute->trial_ends_at)
                                <p class="mb-1"><strong>Trial ends:</strong> {{ $institute->trial_ends_at->format('d M Y') }}</p>
                            @endif
                            <p class="mb-0 text-muted" style="font-size: 0.88rem;">
                                Gateway: {{ strtoupper($gatewayDriver) }}
                            </p>

                            <hr>
                            <h6 class="mb-2">Plan usage</h6>
                            <ul class="mb-0 ps-3" style="font-size: 0.9rem;">
                                <li>Branches: {{ $limits['branches_used'] ?? 0 }}
                                    @if (! is_null($limits['max_branches'] ?? null))
                                        / {{ $limits['max_branches'] }}
                                    @else
                                        / Unlimited
                                    @endif
                                </li>
                                <li>Students: {{ $limits['students_used'] ?? 0 }}
                                    @if (! is_null($limits['max_students'] ?? null))
                                        / {{ $limits['max_students'] }}
                                    @else
                                        / Unlimited
                                    @endif
                                </li>
                                <li>Staff: {{ $limits['staff_used'] ?? 0 }}
                                    @if (! is_null($limits['max_staff'] ?? null))
                                        / {{ $limits['max_staff'] }}
                                    @else
                                        / Unlimited
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-7">
                        <div class="admin-detail-block">
                            @if ($canManageBilling)
                                <h5 class="mb-3">Upgrade plan</h5>
                                <div class="row g-3">
                                    @forelse ($plans as $plan)
                                    <div class="col-md-6">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="mb-1">{{ $plan->name }}</h6>
                                            <p class="text-muted small mb-2">{{ $plan->description }}</p>
                                            <p class="mb-2">
                                                <strong>₹{{ number_format($plan->price_monthly, 0) }}</strong>/mo
                                                · <strong>₹{{ number_format($plan->price_yearly, 0) }}</strong>/yr
                                            </p>
                                            <form method="POST" action="{{ route('admin.billing.checkout') }}" class="mb-2">
                                                @csrf
                                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                                <input type="hidden" name="billing_cycle" value="monthly">
                                                <button type="submit" class="btn btn-primary btn-sm w-100">
                                                    Subscribe monthly
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.billing.checkout') }}">
                                                @csrf
                                                <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                                <input type="hidden" name="billing_cycle" value="yearly">
                                                <button type="submit" class="btn btn-outline-primary btn-sm w-100">
                                                    Subscribe yearly
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-muted mb-0">No public plans available.</p>
                                    </div>
                                @endforelse
                                </div>
                            @else
                                <h5 class="mb-3">Subscription expired</h5>
                                <p class="text-muted mb-0">
                                    Please contact your institute administrator to renew or upgrade the plan.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($canManageBilling && $payments->isNotEmpty())
                    <div class="admin-detail-block mt-4">
                        <h5 class="mb-3">Payment history</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Plan</th>
                                        <th>Cycle</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Gateway</th>
                                        <th>Paid at</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($payments as $payment)
                                        <tr>
                                            <td>{{ $payment->invoice_no ?? '—' }}</td>
                                            <td>{{ $payment->plan?->name ?? '—' }}</td>
                                            <td>{{ ucfirst($payment->billing_cycle) }}</td>
                                            <td>₹{{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ ucfirst($payment->status->value) }}</td>
                                            <td>{{ strtoupper($payment->gateway) }}</td>
                                            <td>{{ $payment->paid_at?->format('d M Y H:i') ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
