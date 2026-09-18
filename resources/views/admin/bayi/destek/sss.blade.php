@extends('layouts.bayi')

@section('title', __('messages.faq'))

@section('panel_content')
<div class="mb-6">
    <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">❓ <span class="gradient-text">{{ __('messages.faq') }}</span></h1>
</div>

<div class="glass rounded-2xl p-6 mb-6">
    @foreach($sorular as $index => $soru)
    <details class="mb-3 last:mb-0 group" {{ $index == 0 ? 'open' : '' }}>
        <summary class="cursor-pointer p-4 rounded-xl bg-yellow-500/5 hover:bg-yellow-500/10 border border-yellow-500/20 flex items-center gap-3">
            <span class="text-yellow-400 group-open:rotate-90 transition-transform">▶</span>
            <span class="font-semibold flex-1">{{ $soru['soru'] }}</span>
        </summary>
        <div class="px-4 py-3 text-sm text-white/80 leading-relaxed">{{ $soru['cevap'] }}</div>
    </details>
    @endforeach
</div>

<div class="glass rounded-2xl p-6 text-center" style="border-color:rgba(59,130,246,.4)">
    <div class="text-4xl mb-3">💡</div>
    <h3 class="font-display text-lg font-bold mb-2">{{ __('messages.didnt_find_answer') }}</h3>
    <p class="text-white/70 mb-4">{{ __('messages.contact_support_team') }}</p>
    <a href="{{ route('admin.bayi.destek.ticket.olustur') }}" class="btn-y">🎫 {{ __('messages.create_support_ticket') }}</a>
</div>
@endsection
