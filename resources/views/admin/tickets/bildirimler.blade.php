@extends('admin._layout')

@section('title', 'Ticket Bildirimleri')

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.tickets.index') }}">Ticketlar</a>
    <span class="sep">/</span>
    <span class="current">Bildirimler</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🔔 Bildirimler
            <span class="badge badge-warning" style="font-size:13px;vertical-align:middle">{{ $bildirimler->total() }}</span>
        </h1>
        <div class="page-subtitle">Bekleyen / yeni cevap gelen ticketlar</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Tüm Ticketlar</span>
        </a>
    </div>
</div>

@if($bildirimler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="bell-off" class="empty-state-icon"></i>
            <h4>Bildirim yok 🎉</h4>
            <p>Tüm ticketlar yanıtlanmış görünüyor. Tebrikler!</p>
        </div>
    </div>
@else
    <div class="section" style="padding:0">
        <div style="display:flex;flex-direction:column">
            @foreach($bildirimler as $b)
                @php
                    $tarihFmt = null;
                    if (!empty($b->updated_at)) {
                        try { $tarihFmt = \Carbon\Carbon::parse($b->updated_at)->diffForHumans(); } catch (\Throwable $e) {}
                    } elseif (!empty($b->tarih)) {
                        try { $tarihFmt = \Carbon\Carbon::parse($b->tarih)->diffForHumans(); } catch (\Throwable $e) {}
                    }
                @endphp
                <a href="{{ route('admin.tickets.detay', $b->id) }}" style="display:flex;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);transition:background 0.15s" onmouseenter="this.style.background='var(--bg-subtle)'" onmouseleave="this.style.background='transparent'">
                    <div style="width:42px;height:42px;border-radius:50%;background:rgba(245,158,11,0.18);color:#f59e0b;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i data-lucide="bell" style="width:18px;height:18px"></i>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;font-size:14px;margin-bottom:2px">
                            #{{ $b->id }} — {{ \Illuminate\Support\Str::limit($b->baslik ?? '—', 60) }}
                        </div>
                        <div style="font-size:12px;color:var(--text-muted)">
                            👤 {{ $b->olusturan_adi ?? '—' }}
                            @if(!empty($b->atanan_adi))
                                → {{ $b->atanan_adi }}
                            @endif
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <span class="badge badge-warning" style="font-size:10.5px">⏳ Bekliyor</span>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px">{{ $tarihFmt ?? '—' }}</div>
                    </div>
                    <i data-lucide="chevron-right" style="color:var(--text-muted);flex-shrink:0"></i>
                </a>
            @endforeach
        </div>
    </div>

    @if($bildirimler->hasPages())
        <div style="margin-top:16px">{{ $bildirimler->links() }}</div>
    @endif
@endif

@endsection