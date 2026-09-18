@extends('layouts.bayi')

@section('title', __('messages.payment_requests'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); $bekleyen = $bekleyen_kazanc ?? 0; @endphp

<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">💳 <span class="gradient-text">{{ __('messages.payment_requests') }}</span></h1>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-8">
    <div class="glass rounded-2xl p-6" style="border-color:rgba(16,185,129,.4)">
        <div class="section-title">💰 {{ __('messages.withdrawable_amount') }}</div>
        <div class="font-display text-4xl font-bold text-emerald-300 mb-2">{{ \App\Helpers\CurrencyHelper::format((float)$bekleyen, $currency) }}</div>
        <p class="text-xs text-white/60">{{ __('messages.min_withdrawal_amount') }}: {{ \App\Helpers\CurrencyHelper::format(100, $currency) }}</p>
    </div>

    <div class="glass rounded-2xl p-6">
        <div class="section-title">➕ {{ __('messages.create_new_payment_request') }}</div>
        <form action="{{ route('admin.bayi.odeme.talep.olustur') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="field-label">{{ __('messages.request_amount') }}</label>
                <input type="number" name="tutar" min="100" max="{{ $bekleyen }}" step="0.01" required>
                <small class="text-xs text-white/50 block mt-1">Min: {{ \App\Helpers\CurrencyHelper::format(100, $currency) }} · Max: {{ \App\Helpers\CurrencyHelper::format((float)$bekleyen, $currency) }}</small>
            </div>
            <div>
                <label class="field-label">{{ __('messages.description_optional') }}</label>
                <textarea name="aciklama" rows="2"></textarea>
            </div>
            <button type="submit" class="btn-y w-full" {{ $bekleyen < 100 ? 'disabled' : '' }}>💳 {{ __('messages.create_payment_request') }}</button>
        </form>
    </div>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <div class="px-6 py-4 border-b border-yellow-500/10">
        <h2 class="font-display text-xl font-bold flex items-center gap-2">📜 {{ __('messages.payment_requests_history') }}</h2>
    </div>
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">#</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.date') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.amount') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.description') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.status') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.transaction_date') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($odeme_talepleri ?? [] as $talep)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 text-white/60">#{{ $talep->id }}</td>
                <td class="px-6 py-4 text-xs text-white/70">{{ isset($talep->created_at) ? date('d.m.Y H:i', strtotime($talep->created_at)) : (isset($talep->tarih) ? date('d.m.Y H:i', strtotime($talep->tarih)) : '-') }}</td>
                <td class="px-6 py-4 text-right font-bold">{{ \App\Helpers\CurrencyHelper::format((float)($talep->tutar ?? $talep->talep_tutari ?? 0), $currency) }}</td>
                <td class="px-6 py-4 text-white/70">{{ $talep->aciklama ?? '-' }}</td>
                <td class="px-6 py-4 text-center">
                    @if($talep->durum == 'beklemede')<span class="badge badge-warning">⏳ {{ __('messages.pending') }}</span>
                    @elseif($talep->durum == 'onaylandi')<span class="badge badge-success">✅ {{ __('messages.approved') }}</span>
                    @else<span class="badge badge-danger">❌ {{ __('messages.rejected') }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-center text-xs text-white/60">{{ isset($talep->onay_tarihi) && $talep->onay_tarihi ? date('d.m.Y H:i', strtotime($talep->onay_tarihi)) : '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">💳</div>{{ __('messages.no_payment_requests_yet') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
