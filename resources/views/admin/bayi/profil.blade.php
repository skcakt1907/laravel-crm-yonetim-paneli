@extends('layouts.bayi')

@section('title', __('messages.my_profile'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); @endphp

<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">👤 <span class="gradient-text">{{ __('messages.my_profile') }}</span></h1>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <form action="{{ route('admin.bayi.profil') }}" method="POST">
            @csrf
            <div class="glass rounded-2xl p-6 mb-5">
                <div class="section-title">🏦 {{ __('messages.bank_info') }}</div>
                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label class="field-label">{{ __('messages.bank_name') }}</label>
                        <input type="text" name="banka_adi" value="{{ old('banka_adi', $bayi->banka_adi ?? '') }}" placeholder="{{ __('messages.bank_name_placeholder') }}">
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.account_holder') }}</label>
                        <input type="text" name="hesap_sahibi" value="{{ old('hesap_sahibi', $bayi->hesap_sahibi ?? '') }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="field-label">IBAN</label>
                        <input type="text" name="iban" value="{{ old('iban', $bayi->iban ?? '') }}" placeholder="TR00 0000 0000 0000 0000 0000 00" maxlength="26">
                    </div>
                </div>
            </div>

            <div class="glass rounded-2xl p-6">
                <div class="section-title">🏢 {{ __('messages.company_info') }}</div>
                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label class="field-label">{{ __('messages.tax_number') }}</label>
                        <input type="text" name="vergi_no" value="{{ old('vergi_no', $bayi->vergi_no ?? '') }}" minlength="10" maxlength="11">
                        <small class="text-xs text-white/50 block mt-1">{{ __('messages.tax_number_help') }}</small>
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.tax_office') }}</label>
                        <input type="text" name="vergi_dairesi" value="{{ old('vergi_dairesi', $bayi->vergi_dairesi ?? '') }}">
                    </div>
                    <div class="md:col-span-2">
                        <label class="field-label">{{ __('messages.address') }}</label>
                        <textarea name="adres" rows="3">{{ old('adres', $bayi->adres ?? '') }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="field-label">{{ __('messages.address_description') }}</label>
                        <textarea name="adres_tarifi" rows="2">{{ old('adres_tarifi', $bayi->adres_tarifi ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="sticky bottom-4 glass rounded-2xl p-4 mt-6 flex items-center justify-between">
                <a href="{{ route('admin.bayi.dashboard') }}" class="btn-o">❌ {{ __('messages.cancel') }}</a>
                <button type="submit" class="btn-y">💾 {{ __('messages.update') }}</button>
            </div>
        </form>
    </div>

    <div>
        <div class="glass rounded-2xl p-6">
            <div class="section-title">📊 {{ __('messages.reseller_info') }}</div>
            @if($bayi)
            <div class="space-y-4">
                <div>
                    <div class="text-xs text-white/60 mb-1">{{ __('messages.reseller_code') }}</div>
                    <div class="font-bold font-mono">{{ $bayi->bayi_kodu }}</div>
                </div>
                <div>
                    <div class="text-xs text-white/60 mb-1">{{ __('messages.commission_rate') }}</div>
                    <div class="font-display text-2xl font-bold gradient-text">%{{ number_format($bayi->komisyon_orani ?? 0, 2) }}</div>
                </div>
                <div class="pt-3 border-t border-yellow-500/10">
                    <div class="text-xs text-white/60 mb-1">{{ __('messages.total_earnings') }}</div>
                    <div class="font-bold text-emerald-300">{{ \App\Helpers\CurrencyHelper::format((float)($bayi->toplam_kazanc ?? 0), $currency) }}</div>
                </div>
                <div>
                    <div class="text-xs text-white/60 mb-1">{{ __('messages.withdrawable_balance') }}</div>
                    <div class="font-bold text-yellow-400">{{ \App\Helpers\CurrencyHelper::format((float)($bayi->cekilebilir_bakiye ?? 0), $currency) }}</div>
                </div>
                <div>
                    <div class="text-xs text-white/60 mb-1">{{ __('messages.total_withdrawn') }}</div>
                    <div class="font-bold">{{ \App\Helpers\CurrencyHelper::format((float)($bayi->cekilen_toplam ?? 0), $currency) }}</div>
                </div>
                <div class="pt-3 border-t border-yellow-500/10">
                    <div class="text-xs text-white/60 mb-1">{{ __('messages.status') }}</div>
                    @if($bayi->onay_durumu == 1)
                        <span class="badge badge-success">✅ {{ __('messages.approved') }}</span>
                    @else
                        <span class="badge badge-warning">⏳ {{ __('messages.pending_approval') }}</span>
                    @endif
                </div>
            </div>
            @else
            <div class="text-center text-white/50 py-6">
                <div class="text-4xl mb-2">⚠️</div>
                {{ __('messages.reseller_not_found') }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
