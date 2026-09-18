@extends('layouts.bayi')

@section('title', __('messages.my_earnings'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); @endphp

<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">💰 <span class="gradient-text">{{ __('messages.my_earnings') }}</span></h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="stat-card glass rounded-2xl p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.total_earnings') }}</div>
            <span class="text-3xl">💰</span>
        </div>
        <div class="count font-display text-2xl font-bold">{{ \App\Helpers\CurrencyHelper::format((float)($kazanc_ozeti['toplam'] ?? 0), $currency) }}</div>
        <div class="text-xs text-yellow-400 mt-2">{{ __('messages.all_time') }}</div>
    </div>
    <div class="stat-card glass rounded-2xl p-6" style="border-color:rgba(16,185,129,.4)">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.paid_earnings') }}</div>
            <span class="text-3xl">✅</span>
        </div>
        <div class="font-display text-2xl font-bold text-emerald-300">{{ \App\Helpers\CurrencyHelper::format((float)($kazanc_ozeti['odenen'] ?? 0), $currency) }}</div>
        <div class="text-xs text-emerald-400 mt-2">{{ __('messages.deposited_to_account') }}</div>
    </div>
    <div class="stat-card glass rounded-2xl p-6" style="border-color:rgba(251,191,36,.4)">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.pending_earnings') }}</div>
            <span class="text-3xl">⏳</span>
        </div>
        <div class="font-display text-2xl font-bold text-amber-300">{{ \App\Helpers\CurrencyHelper::format((float)($kazanc_ozeti['bekleyen'] ?? 0), $currency) }}</div>
        <div class="mt-3">
            <a href="{{ route('admin.bayi.odeme') }}" class="btn-y" style="padding:6px 12px;font-size:12px">💳 {{ __('messages.create_payment_request') }}</a>
        </div>
    </div>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-yellow-500/10">
        <h2 class="font-display text-xl font-bold flex items-center gap-2">📊 {{ __('messages.monthly_earnings_details') }}</h2>
    </div>
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.month') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.sale_count') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.total_earnings') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.average_commission') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($aylik_kazanclar as $kazanc)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 font-semibold">{{ date('F Y', strtotime($kazanc->ay.'-01')) }}</td>
                <td class="px-6 py-4 text-right">{{ $kazanc->satis_adedi }}</td>
                <td class="px-6 py-4 text-right"><span class="badge badge-success">{{ \App\Helpers\CurrencyHelper::format((float)($kazanc->toplam_kazanc ?? 0), $currency) }}</span></td>
                <td class="px-6 py-4 text-right text-white/80">{{ \App\Helpers\CurrencyHelper::format((float)(($kazanc->satis_adedi ?? 0) > 0 ? ($kazanc->toplam_kazanc / $kazanc->satis_adedi) : 0), $currency) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">💰</div>{{ __('messages.no_earnings_yet') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
