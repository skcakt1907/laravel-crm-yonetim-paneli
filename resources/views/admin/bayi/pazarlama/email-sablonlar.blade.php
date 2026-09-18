@extends('layouts.bayi')

@section('title', __('messages.email_sms_templates'))

@section('panel_content')
<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">📧 <span class="gradient-text">{{ __('messages.email_sms_templates') }}</span></h1>
</div>

<div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
    @foreach($sablonlar as $sablon)
    <div class="stat-card glass rounded-2xl p-5">
        <h3 class="font-display font-bold mb-2">{{ $sablon['baslik'] }}</h3>
        <p class="text-xs text-white/60 mb-4 line-clamp-3">{{ Str::limit($sablon['icerik'], 120) }}</p>
        <button type="button" onclick="selectTemplate({{ $sablon['id'] }}, '{{ addslashes($sablon['baslik']) }}', '{{ addslashes($sablon['icerik']) }}')" class="btn-y" style="padding:6px 12px;font-size:12px">✓ {{ __('messages.use_this_template') }}</button>
    </div>
    @endforeach
</div>

<form action="{{ route('admin.bayi.email.gonder') }}" method="POST">
    @csrf
    <div class="glass rounded-2xl p-6 mb-5">
        <div class="section-title">📨 {{ __('messages.send_bulk_email') }}</div>
        <div class="space-y-5">
            <div>
                <label class="field-label">👥 {{ __('messages.recipients') }} <span class="text-rose-300">*</span></label>
                <select name="musteri_id[]" multiple size="6" required>
                    <option value="all" style="background:#1a1a1a">⭐ {{ __('messages.all_customers') }}</option>
                    @foreach($musteriler as $musteri)
                        <option value="{{ $musteri->id }}" style="background:#1a1a1a">{{ $musteri->ad ?? $musteri->adi ?? '-' }} ({{ $musteri->email }})</option>
                    @endforeach
                </select>
                <small class="text-xs text-white/50 block mt-1">{{ __('messages.hold_ctrl_for_multiple') }}</small>
            </div>
            <div>
                <label class="field-label">🏷️ {{ __('messages.subject') }} <span class="text-rose-300">*</span></label>
                <input type="text" name="konu" id="emailKonu" required>
            </div>
            <div>
                <label class="field-label">💬 {{ __('messages.message') }} <span class="text-rose-300">*</span></label>
                <textarea name="mesaj" id="emailMesaj" rows="8" required></textarea>
                <small class="text-xs text-white/50 block mt-1">{{ __('messages.available_variables') }}: <code>{musteri_adi}</code>, <code>{fatura_no}</code>, <code>{hizmet_adi}</code>, <code>{yenileme_tarihi}</code></small>
            </div>
        </div>
    </div>
    <div class="sticky bottom-4 glass rounded-2xl p-4 flex items-center justify-end">
        <button type="submit" class="btn-y">📨 {{ __('messages.send') }}</button>
    </div>
</form>

@push('scripts')
<script src="{{ asset('yonetim/js/email-sablonlar.js') }}"></script>
@endpush
@endsection
