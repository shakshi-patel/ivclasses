<x-dashboard.panel title="Upcoming exams" subtitle="Next 14 days" icon="bx-edit" :action-url="route('admin.exam')">
    @include('admin.dashboard.shared.exam-list', ['exams' => $dashboard->upcomingExams()])
</x-dashboard.panel>
