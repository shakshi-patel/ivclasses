<x-dashboard.panel title="Notice board" subtitle="Latest active notices" icon="bx-news" :action-url="route('admin.notice')">
    @include('admin.dashboard.shared.notice-list', ['notices' => $dashboard->latestNotices()])
</x-dashboard.panel>
