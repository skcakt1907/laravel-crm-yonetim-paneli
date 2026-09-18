@extends('layouts.bayi')

@section('title', __('messages.add_new_customer'))

@section('panel_content')
<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-white/60 mb-2">
        <a href="{{ route('admin.bayi.dashboard') }}" class="hover:text-yellow-400">🏠</a> <span>›</span>
        <a href="{{ route('admin.bayi.musteriler') }}" class="hover:text-yellow-400">Müşterilerim</a> <span>›</span>
        <span>Yeni Müşteri</span>
    </div>
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">➕ <span class="gradient-text">{{ __('messages.add_new_customer') }}</span></h1>
</div>

<form action="{{ route('admin.bayi.musteri.ekle.post') }}" method="POST">
    @csrf
    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="glass rounded-2xl p-6">
                <div class="section-title">👤 {{ __('messages.customer_info') }}</div>
                <div class="grid md:grid-cols-2 gap-5">
                    <div>
                        <label class="field-label">{{ __('messages.first_name') }} <span class="text-rose-300">*</span></label>
                        <input type="text" name="adi" value="{{ old('adi') }}" required>
                        @error('adi')<div class="text-xs text-rose-300 mt-1">⚠️ {{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.last_name') }}</label>
                        <input type="text" name="soyad" value="{{ old('soyad') }}">
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.email') }} <span class="text-rose-300">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required>
                        @error('email')<div class="text-xs text-rose-300 mt-1">⚠️ {{ $message }}</div>@enderror
                        <small class="text-xs text-white/50 block mt-1">{{ __('messages.customer_can_login_email') }}</small>
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.phone') }}</label>
                        <input type="text" name="telefon" value="{{ old('telefon') }}" placeholder="05XX XXX XX XX">
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.password') }} <span class="text-rose-300">*</span></label>
                        <input type="password" name="sifre" required>
                        @error('sifre')<div class="text-xs text-rose-300 mt-1">⚠️ {{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="field-label">{{ __('messages.password_confirm') }} <span class="text-rose-300">*</span></label>
                        <input type="password" name="sifre_confirmation" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="field-label">{{ __('messages.address') }}</label>
                        <textarea name="adres" rows="3">{{ old('adres') }}</textarea>
                    </div>
                    <div class="md:col-span-2 flex items-center justify-between p-4 bg-yellow-500/5 border border-yellow-500/10 rounded-xl">
                        <div>
                            <div class="font-semibold">✅ Aktif</div>
                            <div class="text-xs text-white/60">{{ __('messages.customer_will_be_active') }}</div>
                        </div>
                        <label class="toggle"><input type="checkbox" name="durum" value="1" {{ old('durum', 1) ? 'checked' : '' }}><span class="slider"></span></label>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <div class="glass rounded-2xl p-6" style="border-color:rgba(59,130,246,.4)">
                <div class="section-title">ℹ️ {{ __('messages.information') }}</div>
                <ul class="text-sm space-y-2 text-white/80">
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.email_must_be_unique') }}</span></li>
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.password_min_6_chars') }}</span></li>
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.can_send_email_after') }}</span></li>
                    <li class="flex items-start gap-2"><span>✓</span><span>{{ __('messages.customer_can_login_immediately') }}</span></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="sticky bottom-4 glass rounded-2xl p-4 mt-6 flex items-center justify-between">
        <a href="{{ route('admin.bayi.musteriler') }}" class="btn-o">❌ {{ __('messages.cancel') }}</a>
        <button type="submit" class="btn-y">💾 {{ __('messages.add_customer') }}</button>
    </div>
</form>
@endsection
