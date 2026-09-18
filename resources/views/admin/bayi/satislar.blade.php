@extends('layouts.bayi')

@section('title', __('messages.reseller_my_sales'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); @endphp

<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🛒 <span class="gradient-text">{{ __('messages.reseller_my_sales') }}</span></h1>
    <p class="text-white/60 mt-1">{{ __('messages.all_my_sales') }}</p>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">#</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.customer') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.product') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.amount') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.commission') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.date') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($satislar as $satis)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 text-white/60">#{{ $satis->id }}</td>
                <td class="px-6 py-4 font-semibold">{{ $satis->musteri_adi ?? '-' }}</td>
                <td class="px-6 py-4">{{ $satis->urun_adi ?? '-' }}</td>
                <td class="px-6 py-4 text-right font-bold">{{ \App\Helpers\CurrencyHelper::format((float)($satis->tutar ?? 0), $currency) }}</td>
                <td class="px-6 py-4 text-right font-bold text-yellow-400">{{ \App\Helpers\CurrencyHelper::format((float)($satis->komisyon ?? 0), $currency) }}</td>
                <td class="px-6 py-4 text-right text-xs text-white/60">{{ isset($satis->tarih) ? date('d.m.Y', strtotime($satis->tarih)) : (isset($satis->created_at) ? date('d.m.Y', strtotime($satis->created_at)) : '-') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">🛒</div>{{ __('messages.no_sales_yet') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($satislar->hasPages())
<div class="mt-6 flex justify-center">{{ $satislar->links() }}</div>
@endif
@endsection
