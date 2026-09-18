@extends('layouts.bayi')

@section('title', __('messages.payment_history'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); @endphp

<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">📜 <span class="gradient-text">{{ __('messages.payment_history') }}</span></h1>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">#</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.amount') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.bank') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">IBAN</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.payment_date') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.receipt') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($odemeler as $odeme)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 text-white/60">#{{ $odeme->id }}</td>
                <td class="px-6 py-4 text-right font-bold text-emerald-300">{{ \App\Helpers\CurrencyHelper::format((float)($odeme->tutar ?? 0), $currency) }}</td>
                <td class="px-6 py-4">{{ $odeme->banka_adi ?? '-' }}</td>
                <td class="px-6 py-4 font-mono text-xs">{{ $odeme->iban ?? '-' }}</td>
                <td class="px-6 py-4 text-center text-xs text-white/70">{{ ($odeme->onay_tarihi ?? $odeme->created_at) ? \Carbon\Carbon::parse($odeme->onay_tarihi ?? $odeme->created_at)->format('d.m.Y H:i') : '-' }}</td>
                <td class="px-6 py-4 text-center">
                    @if($odeme->dekont)
                        <a href="{{ asset('uploads/dekontlar/'.$odeme->dekont) }}" target="_blank" class="btn-o" style="padding:6px 12px;font-size:12px">📄 {{ __('messages.view') }}</a>
                    @else - @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">📜</div>{{ __('messages.no_payments_yet') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
