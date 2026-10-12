<x-dashboard.panel title="Admissions" subtitle="New students · last 12 months" icon="bx-trending-up">
    <x-slot:tools>
        <span class="ivd-kpi-inline">12 months <strong data-ivd-total="admissions_total">—</strong></span>
    </x-slot:tools>
    <x-dashboard.chart widget="admissions" :height="290" :params="$chartParams" />
</x-dashboard.panel>
