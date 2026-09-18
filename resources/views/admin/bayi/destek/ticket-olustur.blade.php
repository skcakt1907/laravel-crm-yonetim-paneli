@extends('layouts.bayi')

@section('title', __('messages.create_new_ticket'))

@section('panel_content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm text-white/60 mb-2">
            <a href="{{ route('admin.bayi.destek.tickets') }}" class="hover:text-yellow-400">Destek</a> <span>›</span> <span>Yeni Ticket</span>
        </div>
        <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">🎫 <span class="gradient-text">{{ __('messages.create_new_ticket') }}</span></h1>
    </div>
    <a href="{{ route('admin.bayi.destek.tickets') }}" class="btn-o">← {{ __('messages.back') ?? 'Geri Dön' }}</a>
</div>

<form action="{{ route('admin.bayi.destek.ticket.olustur.post') }}" method="POST">
    @csrf
    <div class="glass rounded-2xl p-6 mb-5">
        <div class="section-title">📝 {{ __('messages.ticket_information') ?? 'Ticket Bilgileri' }}</div>
        <div class="space-y-5">
            <div>
                <label class="field-label">🏷️ {{ __('messages.subject') }} <span class="text-rose-300">*</span></label>
                <input type="text" name="konu" value="{{ old('konu') }}" required>
                @error('konu')<div class="text-xs text-rose-300 mt-1">⚠️ {{ $message }}</div>@enderror
            </div>
            <div class="grid md:grid-cols-2 gap-5">
                <div>
                    <label class="field-label">📁 {{ __('messages.category') }} <span class="text-rose-300">*</span></label>
                    <select name="kategori" required>
                        @foreach($kategoriler as $key => $label)
                            <option value="{{ $key }}" {{ old('kategori') == $key ? 'selected' : '' }} style="background:#1a1a1a">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">🚩 {{ __('messages.priority') }} <span class="text-rose-300">*</span></label>
                    <select name="oncelik" required>
                        <option value="dusuk" {{ old('oncelik') == 'dusuk' ? 'selected' : '' }} style="background:#1a1a1a">{{ __('messages.low') }}</option>
                        <option value="normal" {{ old('oncelik', 'normal') == 'normal' ? 'selected' : '' }} style="background:#1a1a1a">{{ __('messages.normal') }}</option>
                        <option value="yuksek" {{ old('oncelik') == 'yuksek' ? 'selected' : '' }} style="background:#1a1a1a">⚠️ {{ __('messages.high') }}</option>
                        <option value="acil" {{ old('oncelik') == 'acil' ? 'selected' : '' }} style="background:#1a1a1a">🚨 {{ __('messages.urgent') }}</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="field-label">💬 {{ __('messages.message') ?? 'Mesaj' }} <span class="text-rose-300">*</span></label>
                <textarea name="mesaj" rows="8" required>{{ old('mesaj') }}</textarea>
                @error('mesaj')<div class="text-xs text-rose-300 mt-1">⚠️ {{ $message }}</div>@enderror
            </div>
        </div>
    </div>
    <div class="sticky bottom-4 glass rounded-2xl p-4 flex items-center justify-between">
        <a href="{{ route('admin.bayi.destek.tickets') }}" class="btn-o">❌ {{ __('messages.cancel') ?? 'İptal' }}</a>
        <button type="submit" class="btn-y">📨 {{ __('messages.create_new_ticket') }}</button>
    </div>
</form>
@endsection
