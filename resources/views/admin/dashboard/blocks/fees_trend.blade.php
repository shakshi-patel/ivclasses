<x-dashboard.panel title="Fee collection" icon="bx-line-chart"
    :subtitle="$can['expenses'] ? 'Collections vs expenses · last 12 months' : 'Monthly collections · last 12 months'">
    <x-slot:tools>
        <span class="ivd-kpi-inline">12-month total <strong data-ivd-total="fees_total">—</strong></span>
    </x-slot:tools>
    <x-dashboard.chart widget="fees_trend" :height="310" :params="$chartParams" />
</x-dashboard.panel>
