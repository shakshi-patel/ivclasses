@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap no-print">
                    <h4 class="admin-page-title mb-0"><i class="bx bx-file"></i> Fees Follow Up Export</h4>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
                        <i class="bx bx-printer me-1"></i> Print / Save PDF
                    </button>
                </div>

                <div class="mt-3 small text-muted">
                    @if (!empty($filters['branch_id'])) Branch #{{ $filters['branch_id'] }} · @endif
                    @if (!empty($filters['batch_ids'])) Batches: {{ implode(', ', $filters['batch_ids']) }} · @endif
                    @if (!empty($filters['start_date']) || !empty($filters['end_date']))
                        {{ $filters['start_date'] ?? '…' }} → {{ $filters['end_date'] ?? '…' }}
                    @endif
                </div>

                <div class="admin-table-wrap mt-3">
                    <div class="table-responsive">
                        <table class="table admin-list-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sr.</th>
                                    <th>Branch</th>
                                    <th>Student</th>
                                    <th>Father</th>
                                    <th>Contact</th>
                                    <th>Batch</th>
                                    <th>Due Amount</th>
                                    <th>Next Follow Up</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    @php
                                        $student = $invoice->student;
                                        $followUp = $invoice->latestFollowUp;
                                        $fuStatus = $followUp?->status === 'pending' ? 'Pending' : 'Complete';
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $invoice->branch?->name ?? '—' }}</td>
                                        <td>{{ $student?->name ?? '—' }}</td>
                                        <td>{{ $student?->parent?->father_name ?? '—' }}</td>
                                        <td>{{ $student?->parent?->phone ?? '—' }}</td>
                                        <td>{{ $invoice->enrollment?->batch?->name ?? '—' }}</td>
                                        <td>₹{{ number_format((float) $invoice->due_amount, 2) }}</td>
                                        <td>{{ $followUp?->next_follow_up_at?->format('d M Y') ?? '—' }}</td>
                                        <td>{{ $fuStatus }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="9" class="text-center text-muted py-4">No records for selected filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <a href="{{ route('admin.fees.follow_up') }}" class="btn btn-outline-secondary mt-3 no-print">
                    <i class="bx bx-left-arrow-alt me-1"></i> Back
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });</script>
@endpush
