@extends('layouts.bayi')

@section('title', 'Ticket #'.$ticket->id)

@section('panel_content')
@php
    $oncelikler = [
        'dusuk'  => ['badge-secondary', __('messages.low')],
        'normal' => ['badge-yellow', __('messages.normal')],
        'yuksek' => ['badge-warning', '⚠️ '.__('messages.high')],
        'acil'   => ['badge-danger', '🚨 '.__('messages.urgent')],
    ];
    $onc = $oncelikler[$ticket->oncelik] ?? $oncelikler['normal'];
@endphp

<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm text-white/60 mb-2">
            <a href="{{ route('admin.bayi.destek.tickets') }}" class="hover:text-yellow-400">Destek</a> <span>›</span> <span>#{{ $ticket->id }}</span>
        </div>
        <h1 class="font-display text-2xl font-extrabold flex items-center gap-3">🎫 {{ $ticket->konu }}</h1>
    </div>
    <a href="{{ route('admin.bayi.destek.tickets') }}" class="btn-o">← {{ __('messages.back') ?? 'Geri Dön' }}</a>
</div>

<div class="glass rounded-2xl p-6 mb-5">
    <div class="flex items-center gap-3 flex-wrap">
        <span class="badge badge-secondary">📁 {{ $ticket->kategori }}</span>
        <span class="badge {{ $onc[0] }}">{{ $onc[1] }}</span>
        @if($ticket->durum == 'acik')<span class="badge badge-success">🟢 {{ __('messages.open') }}</span>
        @elseif($ticket->durum == 'cevaplandi')<span class="badge badge-yellow">💬 {{ __('messages.answered') }}</span>
        @elseif($ticket->durum == 'cozuldu')<span class="badge badge-success">✅ {{ __('messages.resolved') }}</span>
        @else<span class="badge badge-secondary">🔒 {{ __('messages.closed') }}</span>
        @endif
        <span class="text-xs text-white/50 ml-auto">📅 {{ $ticket->created_at ? \Carbon\Carbon::parse($ticket->created_at)->format('d.m.Y H:i') : '-' }}</span>
    </div>
</div>

<h2 class="font-display text-lg font-bold mb-3">💬 {{ __('messages.messages') ?? 'Mesajlar' }}</h2>

@forelse($mesajlar as $mesaj)
<div class="glass rounded-2xl p-6 mb-3" style="border-color:{{ $mesaj->gonderen_tip === 'bayi' ? 'rgba(59,130,246,.4)' : 'rgba(16,185,129,.4)' }}">
    <div class="flex items-center justify-between flex-wrap gap-3 mb-3">
        <div class="flex items-center gap-2">
            @if($mesaj->gonderen_tip === 'bayi')
                <span class="text-2xl">👤</span><strong class="text-blue-300">{{ __('messages.you') ?? 'Siz' }}</strong>
            @else
                <span class="text-2xl">🛟</span><strong class="text-emerald-300">{{ __('messages.support_team') ?? 'Destek Ekibi' }}</strong>
            @endif
        </div>
        <span class="text-xs text-white/50">📅 {{ $mesaj->created_at ? \Carbon\Carbon::parse($mesaj->created_at)->format('d.m.Y H:i') : '-' }}</span>
    </div>
    <div class="text-white/90 whitespace-pre-wrap leading-relaxed">{!! nl2br(e($mesaj->mesaj)) !!}</div>
</div>
@empty
<div class="glass rounded-xl p-4 text-center text-white/50">{{ __('messages.no_messages_yet') ?? 'Henüz mesaj yok.' }}</div>
@endforelse

<form action="{{ route('admin.bayi.destek.ticket.cevap', $ticket->id) }}" method="POST" class="mt-6">
    @csrf
    <div class="glass rounded-2xl p-6">
        <div class="section-title">✏️ {{ __('messages.reply') ?? 'Cevap Yaz' }}</div>
        <textarea name="mesaj" rows="6" placeholder="{{ __('messages.write_your_reply') ?? 'Cevabınızı yazın...' }}" required>{{ old('mesaj') }}</textarea>
        @error('mesaj')<div class="text-xs text-rose-300 mt-2">⚠️ {{ $message }}</div>@enderror
    </div>
    <div class="glass rounded-2xl p-4 mt-4 flex items-center justify-end">
        <button type="submit" class="btn-y">📨 {{ __('messages.send') ?? 'Gönder' }}</button>
    </div>
</form>
@endsection
