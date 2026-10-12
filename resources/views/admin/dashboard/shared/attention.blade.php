@if (count($attention))
    <div class="ivd-attn-list {{ ($wide ?? false) ? 'ivd-attn-list--grid' : '' }}">
        @foreach ($attention as $item)
            <x-dashboard.attention-item :title="$item['title']" :text="$item['text']"
                :count="$item['count']" :show-count="$item['showCount']" :icon="$item['icon']"
                :tone="$item['tone']"
                :href="$item['route'] && \Illuminate\Support\Facades\Route::has($item['route']) ? route($item['route'], $item['params']) : null" />
        @endforeach
    </div>
@else
    <div class="ivd-all-clear">
        <span class="ivd-all-clear__icon"><i class="bx bx-check-shield"></i></span>
        <strong>You're all caught up</strong>
        <span>{{ $allClear ?? 'Nothing needs your attention right now.' }}</span>
    </div>
@endif
