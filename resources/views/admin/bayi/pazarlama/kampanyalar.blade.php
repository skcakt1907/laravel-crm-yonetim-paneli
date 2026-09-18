@extends('layouts.bayi')

@section('title', __('messages.campaigns'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); @endphp

<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🎉 <span class="gradient-text">{{ __('messages.campaigns') }}</span></h1>
</div>

<div class="glass rounded-2xl overflow-hidden">
    @if(empty($kampanyalar) || $kampanyalar->isEmpty())
    <div class="px-6 py-16 text-center text-white/50">
        <div class="text-5xl mb-3">🎉</div>{{ __('messages.no_active_campaigns') }}
    </div>
    @else
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.title') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.description') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.discount') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.start') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.end') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.status') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($kampanyalar as $kampanya)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 font-semibold">{{ $kampanya->baslik ?? $kampanya->adi ?? '-' }}</td>
                <td class="px-6 py-4 text-white/70">{{ Str::limit($kampanya->aciklama ?? '-', 60) }}</td>
                <td class="px-6 py-4 text-center font-bold text-yellow-400">
                    @if(isset($kampanya->indirim_tipi) && $kampanya->indirim_tipi == 'yuzde')%{{ $kampanya->indirim_miktari ?? 0 }}
                    @else{{ \App\Helpers\CurrencyHelper::format((float)($kampanya->indirim_miktari ?? 0), $currency) }}@endif
                </td>
                <td class="px-6 py-4 text-center text-xs text-white/60">{{ isset($kampanya->baslangic_tarihi) ? \Carbon\Carbon::parse($kampanya->baslangic_tarihi)->format('d.m.Y') : '-' }}</td>
                <td class="px-6 py-4 text-center text-xs text-white/60">{{ isset($kampanya->bitis_tarihi) ? \Carbon\Carbon::parse($kampanya->bitis_tarihi)->format('d.m.Y') : '-' }}</td>
                <td class="px-6 py-4 text-center">
                    @if(isset($kampanya->durum) && $kampanya->durum)<span class="badge badge-success">✅ {{ __('messages.active') }}</span>
                    @else<span class="badge badge-secondary">⚫ {{ __('messages.inactive') }}</span>@endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">🎉</div>{{ __('messages.no_active_campaigns_short') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @endif
</div>
@endsection
