@extends('layouts.bayi')

@section('title', __('messages.notifications'))

@section('panel_content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <div>
        <h1 class="font-display text-3xl font-extrabold flex items-center gap-3">
            🔔 <span class="gradient-text">{{ __('messages.notifications') }}</span>
            @if($okunmamisSayisi > 0)<span class="badge badge-danger">{{ $okunmamisSayisi }} {{ __('messages.unread') }}</span>@endif
        </h1>
    </div>
    <form action="{{ route('admin.bayi.bildirimler.hepsini.oku') }}" method="POST">
        @csrf
        <button type="submit" class="btn-o">✅ {{ __('messages.mark_all_read') }}</button>
    </form>
</div>

<div class="space-y-3">
    @forelse($bildirimler as $bildirim)
    <div class="glass rounded-xl p-5 {{ $bildirim->okundu ? 'opacity-60' : '' }}">
        <div class="flex items-start gap-4">
            <div class="text-2xl flex-shrink-0">{{ $bildirim->okundu ? '📭' : '📬' }}</div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1 flex-wrap">
                    <span class="font-{{ $bildirim->okundu ? 'medium' : 'bold' }}">{{ $bildirim->baslik ?? __('messages.notification') }}</span>
                    @if(!$bildirim->okundu)<span class="w-2 h-2 rounded-full bg-yellow-400"></span>@endif
                    <span class="text-xs text-white/40 ml-auto">{{ $bildirim->created_at ? \Carbon\Carbon::parse($bildirim->created_at)->diffForHumans() : '-' }}</span>
                </div>
                <p class="text-sm text-white/70 mb-2">{{ \Str::limit($bildirim->mesaj ?? '', 200) }}</p>
                @if(!$bildirim->okundu)
                <form action="{{ route('admin.bayi.bildirim.okundu', $bildirim->id) }}" method="POST">
                    @csrf
                    <button class="btn-o" style="padding:4px 10px;font-size:12px">✓ {{ __('messages.mark_as_read') }}</button>
                </form>
                @else
                <span class="badge badge-success">✅ {{ __('messages.read') }}</span>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="glass rounded-2xl p-16 text-center">
        <div class="text-6xl mb-4">🔕</div>
        <p class="text-white/60">{{ __('messages.no_notifications_yet') }}</p>
    </div>
    @endforelse
</div>

@if($bildirimler->hasPages())<div class="mt-6 flex justify-center">{{ $bildirimler->links() }}</div>@endif
@endsection
