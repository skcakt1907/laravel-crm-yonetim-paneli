@extends('layouts.bayi')

@section('title', __('messages.my_referral_link'))

@section('panel_content')
@php $currency = session('currency', 'TRY'); @endphp

<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🔗 <span class="gradient-text">{{ __('messages.referral_link_qr') }}</span></h1>
    <p class="text-white/60 mt-1">{{ __('messages.share_link_to_earn') }}</p>
</div>

<div class="grid lg:grid-cols-2 gap-6 mb-8">
    <div class="glass rounded-2xl p-6">
        <div class="section-title">🔗 {{ __('messages.your_referral_link') }}</div>
        <div class="mb-4">
            <label class="field-label">{{ __('messages.referral_url') }}</label>
            <div class="flex gap-2">
                <input type="text" id="referansLink" value="{{ $referansLink }}" readonly>
                <button type="button" onclick="copyLink()" class="btn-y" style="white-space:nowrap">📋 {{ __('messages.copy') }}</button>
            </div>
        </div>
        <div class="text-xs uppercase tracking-wider text-yellow-400 font-bold mb-3">📤 {{ __('messages.share_buttons') }}</div>
        <div class="grid grid-cols-2 gap-2">
            <a href="https://wa.me/?text={{ urlencode($referansLink) }}" target="_blank" class="btn-o text-center" style="padding:8px;font-size:13px">💬 WhatsApp</a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($referansLink) }}" target="_blank" class="btn-o text-center" style="padding:8px;font-size:13px">📘 Facebook</a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode($referansLink) }}" target="_blank" class="btn-o text-center" style="padding:8px;font-size:13px">🐦 Twitter</a>
            <a href="mailto:?subject={{ __('messages.referral_invitation') ?? 'İşbirliği Daveti' }}&body={{ urlencode($referansLink) }}" class="btn-o text-center" style="padding:8px;font-size:13px">📧 Email</a>
        </div>
    </div>

    <div class="glass rounded-2xl p-6 text-center">
        <div class="section-title">📱 {{ __('messages.qr_code') }}</div>
        <p class="text-xs text-white/60 mb-4">{{ __('messages.use_qr_in_materials') }}</p>
        @if(isset($qrCodeUrl))
            <img src="{{ $qrCodeUrl }}" alt="QR Code" class="mx-auto mb-4 rounded-xl" style="max-width:240px;background:#fff;padding:10px">
            <a href="{{ $qrCodeUrl }}" download="referans-qr.png" target="_blank" class="btn-y">⬇️ {{ __('messages.download_qr') }}</a>
        @else
            <p class="text-white/50">{{ __('messages.qr_could_not_be_generated') }}</p>
        @endif
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="stat-card glass rounded-2xl p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.total_clicks') }}</div>
            <span class="text-3xl">👆</span>
        </div>
        <div class="count font-display text-3xl font-bold">{{ $stats['toplam_tiklanma'] }}</div>
    </div>
    <div class="stat-card glass rounded-2xl p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.total_registrations') }}</div>
            <span class="text-3xl">📝</span>
        </div>
        <div class="count font-display text-3xl font-bold">{{ $stats['toplam_kayit'] }}</div>
    </div>
    <div class="stat-card glass rounded-2xl p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.active_customer') }}</div>
            <span class="text-3xl">⭐</span>
        </div>
        <div class="count font-display text-3xl font-bold">{{ $stats['aktif_musteri'] }}</div>
    </div>
    <div class="stat-card glass rounded-2xl p-6">
        <div class="flex items-start justify-between mb-3">
            <div class="text-xs text-white/60 uppercase tracking-wider">{{ __('messages.total_earnings') }}</div>
            <span class="text-3xl">💰</span>
        </div>
        <div class="count font-display text-xl font-bold">{{ \App\Helpers\CurrencyHelper::format((float)($stats['toplam_kazanc'] ?? 0), $currency) }}</div>
    </div>
</div>

@push('scripts')
<script>
function copyLink(){
    const i = document.getElementById('referansLink');
    i.select(); document.execCommand('copy');
    alert('✅ Link kopyalandı!');
}
</script>
@endpush
@endsection
