@extends('layouts.bayi')

@section('title', __('messages.my_payment_requests'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); @endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">💳 <span class="gradient-text">{{ __('messages.my_payment_requests') }}</span></h1>
    <a href="{{ route('admin.bayi.odeme.talep.olustur') }}" class="btn-y">➕ {{ __('messages.create_new_request') }}</a>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">#</th>
                <th class="text-right font-semibold px-6 py-4 text-yellow-400">{{ __('messages.amount') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.status') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.request_date') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.transaction_date') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($talepler as $talep)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4 text-white/60">#{{ $talep->id }}</td>
                <td class="px-6 py-4 text-right font-bold">{{ \App\Helpers\CurrencyHelper::format((float)($talep->tutar ?? 0), $currency) }}</td>
                <td class="px-6 py-4 text-center">
                    @if($talep->durum == 'beklemede')<span class="badge badge-warning">⏳ {{ __('messages.pending') }}</span>
                    @elseif($talep->durum == 'onaylandi')<span class="badge badge-success">✅ {{ __('messages.approved') }}</span>
                    @else<span class="badge badge-danger">❌ {{ __('messages.rejected') }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-center text-xs text-white/60">{{ $talep->talep_tarihi ? date('d.m.Y', strtotime($talep->talep_tarihi)) : '-' }}</td>
                <td class="px-6 py-4 text-center text-xs text-white/60">{{ $talep->islem_tarihi ? date('d.m.Y', strtotime($talep->islem_tarihi)) : '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">💳</div>{{ __('messages.no_payment_request_yet') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($talepler->hasPages())<div class="mt-6 flex justify-center">{{ $talepler->links() }}</div>@endif
@endsection
