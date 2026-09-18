{{-- Reusable 4-card stat row. Kullanım:
     @include('admin._partials.stat-cards', ['statCards' => [
        ['label'=>'TOPLAM','value'=>123,'icon'=>'👥','color'=>'#3b82f6','text'=>'#60a5fa'],
        ...
     ]])
--}}
@if(!empty($statCards) && is_iterable($statCards))
<div class="grid grid-cols-2 md:grid-cols-{{ count($statCards) >= 4 ? 4 : count($statCards) }} gap-4 mb-6">
@foreach($statCards as $c)
<div class="stat-card glass rounded-2xl p-5 flex items-center justify-between" style="border-top:4px solid {{ $c['color'] ?? '#b8b62e' }}">
<div>
<div class="text-xs uppercase tracking-wider font-bold mb-2" style="color:{{ $c['text'] ?? ($c['color'] ?? '#d4d066') }}">{{ $c['label'] ?? '—' }}</div>
<div class="font-display text-4xl font-extrabold count" style="color:{{ $c['text'] ?? '#fff' }}">{{ is_numeric($c['value'] ?? null) ? number_format($c['value']) : ($c['value'] ?? '—') }}</div>
@if(!empty($c['sub']))<div class="text-xs text-white/50 mt-1">{{ $c['sub'] }}</div>@endif
</div>
<div class="text-4xl opacity-40">{{ $c['icon'] ?? '📊' }}</div>
</div>
@endforeach
</div>
@endif
