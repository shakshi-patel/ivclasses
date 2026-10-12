@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Welcome / Hero -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card admin-hero-card">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <span class="chip mb-2"><i class='bx bx-calendar-check'></i>&nbsp;
                                {{ now()->format('d M, Y') }}</span>
                            <h4 class="mb-1 fw-bold" style="color: var(--iv-primary);">
                                Welcome back, {{ auth()->user()?->name }}
                            </h4>
                            <p class="mb-0 text-muted">
                                Here's what's happening with {{ auth()->user()?->institute?->name ?? config('app.name') }} today.
                            </p>
                        </div>
                        <div class="d-none" data-module-help-slot></div>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
