@extends('layouts.bayi')

@section('title', __('messages.referral_statistics'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); @endphp

<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">📈 <span class="gradient-text">{{ __('messages.referral_statistics') }}</span></h1>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="stat-card glass rounded-2xl p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.total_referrals') }}</div>
            <span class="text-3xl">👥</span>
        </div>
        <div class="count font-display text-3xl font-bold">{{ $referanslar ? $referanslar->total() : 0 }}</div>
    </div>
    <div class="stat-card glass rounded-2xl p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.active_customer') }}</div>
            <span class="text-3xl">⭐</span>
        </div>
        <div class="count font-display text-3xl font-bold">{{ $aktifMusteriSayisi ?? 0 }}</div>
    </div>
    <div class="stat-card glass rounded-2xl p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.this_month_referrals') }}</div>
            <span class="text-3xl">📅</span>
        </div>
        <div class="count font-display text-3xl font-bold">{{ collect($aylikReferans ?? [])->filter(fn($r) => date('Y-m', strtotime($r->tarih ?? $r->created_at ?? '')) == date('Y-m'))->count() }}</div>
    </div>
    <div class="stat-card glass rounded-2xl p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.total_earnings') }}</div>
            <span class="text-3xl">💰</span>
        </div>
        <div class="count font-display text-xl font-bold">{{ \App\Helpers\CurrencyHelper::format((float)($toplamKazanc ?? 0), $currency) }}</div>
    </div>
</div>

<div class="glass rounded-2xl overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-yellow-500/10">
        <h2 class="font-display text-xl font-bold flex items-center gap-2">📊 {{ __('messages.monthly_referral_statistics') }}</h2>
    </div>
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.month') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.referral_count') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.active_customer') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.total_earnings') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($aylikReferans ?? [] as $aylik)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 font-semibold">{{ isset($aylik->ay) ? date('F Y', strtotime($aylik->ay.'-01')) : (isset($aylik->tarih) ? date('F Y', strtotime($aylik->tarih)) : '-') }}</td>
                <td class="px-6 py-4 text-right">{{ $aylik->referans_sayisi ?? $aylik->sayi ?? 0 }}</td>
                <td class="px-6 py-4 text-right">{{ $aylik->aktif_musteri ?? 0 }}</td>
                <td class="px-6 py-4 text-right"><span class="badge badge-success">{{ \App\Helpers\CurrencyHelper::format((float)($aylik->toplam_kazanc ?? 0), $currency) }}</span></td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">📊</div>{{ __('messages.no_monthly_stats_yet') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-yellow-500/10">
        <h2 class="font-display text-xl font-bold flex items-center gap-2">👥 {{ __('messages.my_referrals') }}</h2>
    </div>
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">#</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.customer_name') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.email') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.registration_date') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.status') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.earnings') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($referanslar ?? [] as $referans)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 text-white/60">{{ $referans->id ?? '-' }}</td>
                <td class="px-6 py-4 font-semibold">{{ $referans->musteri_adi ?? $referans->ad ?? '-' }}</td>
                <td class="px-6 py-4 text-white/80">{{ $referans->email ?? '-' }}</td>
                <td class="px-6 py-4 text-center text-xs text-white/60">{{ isset($referans->kayit_tarihi) ? date('d.m.Y', strtotime($referans->kayit_tarihi)) : (isset($referans->created_at) ? date('d.m.Y', strtotime($referans->created_at)) : '-') }}</td>
                <td class="px-6 py-4 text-center">
                    @if(isset($referans->durum))
                        @if($referans->durum == 'aktif')<span class="badge badge-success">✅ {{ __('messages.active') }}</span>
                        @else<span class="badge badge-secondary">⚫ {{ __('messages.inactive') }}</span>
                        @endif
                    @else <span class="badge badge-secondary">-</span> @endif
                </td>
                <td class="px-6 py-4 text-right font-bold text-yellow-400">{{ \App\Helpers\CurrencyHelper::format((float)($referans->kazanc ?? 0), $currency) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">👥</div>{{ __('messages.no_referrals_yet') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($referanslar && $referanslar->hasPages())<div class="p-4 flex justify-center">{{ $referanslar->links() }}</div>@endif
</div>
@endsection
