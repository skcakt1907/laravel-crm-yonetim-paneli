@extends('layouts.bayi')

@section('title', __('messages.promo_codes'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); $sembol = \App\Helpers\CurrencyHelper::getSymbol($currency); @endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🎟️ <span class="gradient-text">{{ __('messages.promo_codes') }}</span></h1>
    <button type="button" onclick="document.getElementById('yeniKodModal').classList.remove('hidden')" class="btn-y">➕ {{ __('messages.create_new_code') }}</button>
</div>

<div class="glass rounded-2xl overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="text-xs uppercase border-b border-yellow-500/10" style="background:rgba(250,204,21,0.05)">
                <th class="text-left font-semibold px-6 py-4 text-yellow-400">{{ __('messages.code') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.discount') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.usage') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.approval_status') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.expiry_date') }}</th>
                <th class="text-center font-semibold px-6 py-4 text-yellow-400">{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody class="text-sm">
            @forelse($kodlar as $kod)
            <tr class="data-row border-b border-yellow-500/5">
                <td class="px-6 py-4"><code class="text-yellow-400 font-mono font-bold">{{ $kod->kod }}</code></td>
                <td class="px-6 py-4 text-center font-bold">
                    @if($kod->indirim_tipi == 'yuzde')%{{ $kod->indirim_miktari }}
                    @else{{ \App\Helpers\CurrencyHelper::format((float)($kod->indirim_miktari ?? 0), $currency) }}@endif
                </td>
                <td class="px-6 py-4 text-center">{{ $kod->kullanim_sayisi ?? 0 }} / {{ $kod->kullanim_limiti ?? '∞' }}</td>
                <td class="px-6 py-4 text-center">
                    @php $od = $kod->onay_durumu ?? 0; @endphp
                    @if($od == 0)<span class="badge badge-warning">⏳ {{ __('messages.pending_approval') }}</span>
                    @elseif($od == 1)<span class="badge badge-success">✅ {{ __('messages.approved') }}</span>
                    @else
                        <span class="badge badge-danger">❌ {{ __('messages.rejected') }}</span>
                        @if($kod->red_nedeni)<div class="text-xs text-white/50 mt-1">{{ $kod->red_nedeni }}</div>@endif
                    @endif
                </td>
                <td class="px-6 py-4 text-center text-xs text-white/60">{{ $kod->bitis_tarihi ? \Carbon\Carbon::parse($kod->bitis_tarihi)->format('d.m.Y') : __('messages.unlimited') }}</td>
                <td class="px-6 py-4 text-center">
                    <form action="{{ route('admin.bayi.promosyon.sil', $kod->id) }}" method="POST" onsubmit="return confirm('{{ __('messages.confirm_delete') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-o" style="padding:6px 10px;font-size:12px;border-color:rgba(239,68,68,.4);color:#fca5a5">🗑️</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center text-white/50">
                <div class="text-5xl mb-3">🎟️</div>{{ __('messages.no_promo_codes_yet') }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="yeniKodModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background:rgba(0,0,0,.7);backdrop-filter:blur(4px)">
    <div class="glass rounded-2xl p-6 w-full max-w-md mx-4 max-h-screen overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-display text-xl font-bold">🎟️ {{ __('messages.new_promo_code') }}</h3>
            <button type="button" onclick="document.getElementById('yeniKodModal').classList.add('hidden')" class="text-2xl text-white/60 hover:text-white">×</button>
        </div>
        <form action="{{ route('admin.bayi.promosyon.olustur') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="field-label">{{ __('messages.code') }} <span class="text-rose-300">*</span></label>
                <input type="text" name="kod" required style="text-transform:uppercase">
                <small class="text-xs text-white/50 block mt-1">{{ __('messages.example') }}: YILBASI2025</small>
            </div>
            <div>
                <label class="field-label">{{ __('messages.discount_type') }} <span class="text-rose-300">*</span></label>
                <select name="indirim_tipi" required>
                    <option value="yuzde" style="background:#1a1a1a">{{ __('messages.percentage') }} (%)</option>
                    <option value="tutar" style="background:#1a1a1a">{{ __('messages.fixed_amount') }} ({{ $sembol }})</option>
                </select>
            </div>
            <div>
                <label class="field-label">{{ __('messages.discount_amount') }} <span class="text-rose-300">*</span></label>
                <input type="number" name="indirim_miktari" step="0.01" min="0" required>
            </div>
            <div>
                <label class="field-label">{{ __('messages.usage_limit') }}</label>
                <input type="number" name="kullanim_limiti" min="1">
                <small class="text-xs text-white/50 block mt-1">{{ __('messages.leave_empty_for_unlimited') }}</small>
            </div>
            <div>
                <label class="field-label">{{ __('messages.expiry_date') }}</label>
                <input type="date" name="bitis_tarihi">
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('yeniKodModal').classList.add('hidden')" class="btn-o">{{ __('messages.cancel') }}</button>
                <button type="submit" class="btn-y">{{ __('messages.create') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
