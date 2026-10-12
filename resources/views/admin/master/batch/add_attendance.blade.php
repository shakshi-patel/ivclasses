@extends('admin.layouts.layout')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="admin-main-card">
            <div class="admin-main-card-inner">
                <h4 class="admin-page-title">
                    <i class="bx bx-calendar-check"></i>
                    Add Attendance
                </h4>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0" style="font-size: 0.86rem; color: var(--color-text-secondary);">
                        <li class="breadcrumb-item">
                            <a href="{{ route('dashboard') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.master.batch') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Batches</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('admin.master.batch.view') }}" class="text-decoration-none"
                                style="color: var(--color-text-secondary);">Morning A</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page" style="color: var(--iv-primary);">Add Attendance</li>
                    </ol>
                </nav>

                <div class="alert mt-4 mb-3" role="alert"
                    style="background: rgba(var(--iv-secondary-rgb), 0.18); border: 1px solid rgba(var(--iv-secondary-rgb), 0.35); color: #8a6418;">
                    When you submit Attendance, it is successfully added to the report.
                </div>

                <div class="master-info-bar mb-3">
                    <strong>Today : 21 August 2026</strong>
                    <span>Total Students: 26</span>
                </div>

                <div class="admin-detail-block">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bx bx-id-card" style="color: var(--iv-primary);"></i>
                        <h5 class="mb-0" style="color: var(--iv-primary-dark);">Batch Student ( Roll No. )</h5>
                    </div>

                    <div class="master-roll-grid" id="rollGrid">
                        @php
                            $students = [
                                [1, 'Gelot Pulkit'],
                                [2, 'Patel Rutvi'],
                                [3, 'Shah Aarav'],
                                [4, 'Mehta Diya'],
                                [5, 'Joshi Kabir'],
                                [6, 'Desai Anaya'],
                                [7, 'Raval Harsh'],
                                [8, 'Trivedi Isha'],
                                [9, 'Parmar Yash'],
                                [10, 'Soni Naina'],
                                [11, 'Chauhan Veer'],
                                [12, 'Dave Kiara'],
                                [13, 'Bhatt Rohan'],
                                [14, 'Gandhi Mira'],
                                [15, 'Panchal Om'],
                            ];
                        @endphp
                        @foreach ($students as $stu)
                            <div class="master-roll-chip" data-state="present" role="button" tabindex="0">
                                <div class="master-roll-no">{{ $stu[0] }}</div>
                                <div class="master-roll-name">{{ $stu[1] }}</div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 mb-3 px-3 py-2 rounded-3"
                        style="background: rgba(var(--iv-primary-rgb), 0.08); color: var(--iv-primary-dark); font-size: 0.86rem;">
                        Note: Click a student to cycle Present → Absent → Late Present. Selected red = absent.
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <div class="master-att-stat is-present">
                                <i class="bx bx-check-circle"></i>
                                <span>Present: <strong id="cntPresent">15</strong></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="master-att-stat is-absent">
                                <i class="bx bx-x-circle"></i>
                                <span>Absent: <strong id="cntAbsent">0</strong></span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="master-att-stat is-late">
                                <i class="bx bx-time-five"></i>
                                <span>Late Present: <strong id="cntLate">0</strong></span>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn w-100" style="background: var(--iv-primary); color: #fff; min-height: 48px;">
                        Submit Attendance
                    </button>
                    <div class="text-muted small mt-2">Absent Student List (design only)</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const chips = document.querySelectorAll('#rollGrid .master-roll-chip');
            const elP = document.getElementById('cntPresent');
            const elA = document.getElementById('cntAbsent');
            const elL = document.getElementById('cntLate');

            function refresh() {
                let p = 0, a = 0, l = 0;
                chips.forEach(function(chip) {
                    const s = chip.dataset.state;
                    chip.classList.remove('is-absent', 'is-late');
                    if (s === 'absent') {
                        chip.classList.add('is-absent');
                        a++;
                    } else if (s === 'late') {
                        chip.classList.add('is-late');
                        l++;
                    } else {
                        p++;
                    }
                });
                elP.textContent = p;
                elA.textContent = a;
                elL.textContent = l;
            }

            function cycle(chip) {
                const order = ['present', 'absent', 'late'];
                const i = order.indexOf(chip.dataset.state || 'present');
                chip.dataset.state = order[(i + 1) % order.length];
                refresh();
            }

            chips.forEach(function(chip) {
                chip.addEventListener('click', function() { cycle(chip); });
                chip.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        cycle(chip);
                    }
                });
            });
            refresh();
        })();
    </script>
@endpush
