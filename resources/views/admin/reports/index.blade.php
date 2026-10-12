@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
                    <div>
                        <h4 class="admin-page-title">
                            <i class="bx bx-bar-chart-alt-2"></i>
                            Reports
                        </h4>
                        <p class="text-muted small mb-0 mt-1">Theme-matched dynamic reports — filter, review, print.</p>
                    </div>
                </div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 admin-breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Reports</li>
                    </ol>
                </nav>

                <div class="row g-3 mt-3">
                    @forelse ($cards as $card)
                        <div class="col-md-6 col-xl-4">
                            <a href="{{ $card['route'] }}" class="report-hub-card text-decoration-none">
                                <div class="report-hub-icon">
                                    <i class="bx {{ $card['icon'] }}"></i>
                                </div>
                                <div>
                                    <div class="report-hub-title">{{ $card['title'] }}</div>
                                    <div class="report-hub-desc">{{ $card['desc'] }}</div>
                                </div>
                                <i class="bx bx-right-arrow-alt report-hub-arrow"></i>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <p class="text-muted mb-0">No report modules available for your role.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .report-hub-card {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 18px;
            border-radius: 14px;
            border: 1px solid rgba(var(--iv-primary-rgb), 0.14);
            background: linear-gradient(135deg, #fff 0%, rgba(var(--iv-primary-rgb), 0.04) 100%);
            color: inherit;
            height: 100%;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }
        .report-hub-card:hover {
            border-color: rgba(var(--iv-primary-rgb), 0.35);
            box-shadow: 0 8px 22px rgba(var(--iv-primary-rgb), 0.1);
            transform: translateY(-2px);
            color: inherit;
        }
        .report-hub-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(var(--iv-primary-rgb), 0.12);
            color: var(--iv-primary-dark);
            flex-shrink: 0;
        }
        .report-hub-icon i { font-size: 1.35rem; }
        .report-hub-title {
            font-weight: 750;
            color: var(--iv-primary-dark);
            font-size: 0.98rem;
        }
        .report-hub-desc {
            font-size: 0.8rem;
            color: var(--iv-text-secondary);
            margin-top: 2px;
        }
        .report-hub-arrow {
            margin-left: auto;
            color: var(--iv-primary);
            font-size: 1.2rem;
        }
    </style>
@endpush
