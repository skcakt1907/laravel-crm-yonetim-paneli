@extends('layouts.bayi')

@section('title', __('messages.create_payment_request'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); @endphp

<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-white/60 mb-2">
        <a href="{{ route('admin.bayi.dashboard') }}" class="hover:text-yellow-400">🏠</a> <span>›</span>
        <a href="{{ route('admin.bayi.odeme.talepleri') }}" class="hover:text-yellow-400">Ödeme Talepleri</a> <span>›</span>
        <span>Yeni Talep</span>
    </div>
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">💳 <span class="gradient-text">{{ __('messages.create_payment_request') }}</span></h1>
</div>

@if($bayi)
<div class="glass rounded-2xl p-5 mb-5 flex items-center justify-between" style="border-color:rgba(16,185,129,.4)">
    <div>
        <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.your_withdrawable_balance') }}</div>
        <div class="font-display text-2xl font-bold text-emerald-300 mt-1">{{ \App\Helpers\CurrencyHelper::format((float)($bayi->cekilebilir_bakiye ?? 0), $currency) }}</div>
    </div>
    <span class="text-5xl">💰</span>
</div>
@endif

<form action="{{ route('admin.bayi.odeme.talep.olustur') }}" method="POST">
    @csrf
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="glass rounded-2xl p-6">
                <div class="section-title">📝 {{ __('messages.payment_request_form') }}</div>
                <div class="space-y-5">
                    <div>
                        <label class="field-label">💰 {{ __('messages.withdrawal_amount') }} <span class="text-rose-300">*</span></label>
                        <input type="number" step="0.01" min="50" name="tutar" value="{{ old('tutar') }}" required>
                        <small class="text-xs text-white/50 block mt-1">{{ __('messages.min_withdrawal_amount') }}: {{ \App\Helpers\CurrencyHelper::format(50, $currency) }}</small>
                    </div>
                    <div>
                        <label class="field-label">🏦 {{ __('messages.bank_name') }} <span class="text-rose-300">*</span></label>
                        <input type="text" name="banka_adi" value="{{ old('banka_adi', $bayi->banka_adi ?? '') }}" required>
                    </div>
                    <div>
                        <label class="field-label">🔢 IBAN <span class="text-rose-300">*</span></label>
                        <input type="text" name="iban" value="{{ old('iban', $bayi->iban ?? '') }}" placeholder="TR00 0000 0000 0000 0000 0000 00" maxlength="26" required>
                    </div>
                    <div>
                        <label class="field-label">👤 {{ __('messages.account_holder') }} <span class="text-rose-300">*</span></label>
                        <input type="text" name="hesap_sahibi" value="{{ old('hesap_sahibi', $bayi->hesap_sahibi ?? '') }}" required>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="glass rounded-2xl p-6">
                <div class="section-title">ℹ️ {{ __('messages.information') }}</div>
                <ul class="text-sm space-y-3 text-white/80">
                    <li class="flex items-start gap-2"><span>✅</span><span>{{ __('messages.min_withdrawal_amount') }} <strong>{{ \App\Helpers\CurrencyHelper::format(50, $currency) }}</strong></span></li>
                    <li class="flex items-start gap-2"><span>✅</span><span>{{ __('messages.requests') }} <strong>1-3</strong> {{ __('messages.business_days') }}</span></li>
                    <li class="flex items-start gap-2"><span>✅</span><span>{{ __('messages.make_sure_iban_correct') }}</span></li>
                    <li class="flex items-start gap-2"><span>⚠️</span><span>{{ __('messages.approved_requests') }} <strong>{{ __('messages.cannot_be_reversed') }}</strong></span></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="sticky bottom-4 glass rounded-2xl p-4 mt-6 flex items-center justify-between">
        <a href="{{ route('admin.bayi.odeme.talepleri') }}" class="btn-o">❌ {{ __('messages.cancel') }}</a>
        <button type="submit" class="btn-y">📨 {{ __('messages.create_request') }}</button>
    </div>
</form>
@endsection
