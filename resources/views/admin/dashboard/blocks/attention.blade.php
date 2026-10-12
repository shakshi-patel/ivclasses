<x-dashboard.panel title="Needs attention" subtitle="What to act on today" icon="bx-bell">
    @include('admin.dashboard.shared.attention', [
        'allClear' => 'No overdue fees, pending approvals or follow-ups right now.',
    ])
</x-dashboard.panel>
