<x-dashboard.panel title="Lead sources" subtitle="Where inquiries come from" icon="bx-target-lock">
    <x-slot:tools>
        <div class="ivd-mini-stats">
            <span class="ivd-pill ivd-tone-info"><span data-ivd-total="lead_new">0</span>&nbsp;new</span>
            <span class="ivd-pill ivd-tone-warning"><span data-ivd-total="lead_follow_up">0</span>&nbsp;follow-up</span>
            <span class="ivd-pill ivd-tone-success"><span data-ivd-total="lead_converted">0</span>&nbsp;won</span>
        </div>
    </x-slot:tools>
    <x-dashboard.chart widget="lead_sources" :height="290" :params="$chartParams" />
</x-dashboard.panel>
