@extends('layouts.bayi')

@section('title', __('messages.reseller_my_customers'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); @endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">👥 <span class="gradient-text">{{ __('messages.reseller_my_customers') }}</span></h1>
    <a href="{{ route('admin.bayi.musteri.ekle') }}" class="btn-y">➕ Yeni Müşteri</a>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">#</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.full_name') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.email') }}</th>
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.phone') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.sale_count') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.reseller_total_earnings') }}</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.last_sale') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($musteriler as $musteri)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 text-white/60">#{{ $musteri->uye->id ?? '-' }}</td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg bg-yellow-500/20 flex items-center justify-center text-yellow-400 font-bold">{{ strtoupper(substr($musteri->uye->ad ?? '?', 0, 1)) }}</div>
                        <div class="font-semibold">{{ $musteri->uye->ad ?? '-' }} {{ $musteri->uye->soyad ?? '' }}</div>
                    </div>
                </td>
                <td class="px-6 py-4 text-white/80">{{ $musteri->uye->email ?? '-' }}</td>
                <td class="px-6 py-4 text-white/80">{{ $musteri->uye->telefon ?? '-' }}</td>
                <td class="px-6 py-4 text-center">
                    <span class="badge {{ ($musteri->satis_adedi ?? 0) > 0 ? 'badge-success' : 'badge-secondary' }}">{{ $musteri->satis_adedi ?? 0 }}</span>
                </td>
                <td class="px-6 py-4 text-right font-bold text-yellow-400">{{ \App\Helpers\CurrencyHelper::format((float)($musteri->toplam_kazanc ?? 0), $currency) }}</td>
                <td class="px-6 py-4 text-right text-xs text-white/60">{{ $musteri->son_satis ? \Carbon\Carbon::parse($musteri->son_satis)->format('d.m.Y H:i') : '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">👥</div>
                <div class="mb-4">{{ __('messages.no_customers_yet') }}</div>
                <a href="{{ route('admin.bayi.musteri.ekle') }}" class="btn-y">➕ Yeni Müşteri Ekle</a>
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
