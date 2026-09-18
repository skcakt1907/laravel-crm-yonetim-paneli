@extends('admin._layout')

@section('title', 'Bildirimler')

@push('head')
<style>
    .notif-row {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
        transition: background 0.15s;
    }
    .notif-row:hover { background: var(--bg-subtle); }
    .notif-row.unread { background: rgba(184,182,46,0.04); }
    .notif-row.unread:hover { background: rgba(184,182,46,0.08); }

    .notif-icon {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .notif-body { flex: 1; min-width: 0; }
    .notif-title { font-weight: 700; font-size: 14px; color: var(--text); margin-bottom: 2px; }
    .notif-row.unread .notif-title::before {
        content: '';
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--brand);
        margin-right: 6px;
        vertical-align: middle;
    }
    .notif-message { font-size: 12.5px; color: var(--text-secondary); line-height: 1.5; }
    .notif-time { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

    .notif-actions { display: flex; gap: 4px; flex-shrink: 0; align-self: center; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Bildirimler</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">🔔 Bildirimler
            <span class="badge badge-brand" style="font-size:13px;vertical-align:middle">{{ $bildirimler->total() }}</span>
        </h1>
        <div class="page-subtitle">Sistem bildirimleri ve uyarılar</div>
    </div>
    <div class="page-actions">
        @if(($okunmamis ?? 0) > 0)
            <form action="{{ route('admin.bildirimler.tumunu.oku') }}" method="POST" style="margin:0" onsubmit="return confirm('Tüm bildirimleri okundu olarak işaretle?');">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i data-lucide="check-check"></i>
                    <span>Tümünü Okundu İşaretle</span>
                </button>
            </form>
        @endif
    </div>
</div>

@php
    // Stat hesapları
    $totalBildirim = $bildirimler->total();
    $okunmamisCount = $okunmamis ?? 0;
    $okunmusCount = max(0, $totalBildirim - $okunmamisCount);

    // Bugün
    $bugun = 0;
    try {
        $hasTarih = \Schema::hasTable('bildirimler') && \Schema::hasColumn('bildirimler', 'created_at');
        if ($hasTarih) {
            $bugun = \DB::table('bildirimler')
                ->whereDate('created_at', now()->toDateString())
                ->count();
        }
    } catch (\Throwable $e) {}
@endphp

{{-- 4 Stat Card --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="bell"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Bildirim</div>
            <div class="stat-card-value">{{ $totalBildirim }}</div>
        </div>
    </div>

    <div class="stat-card" style="@if($okunmamisCount > 0)border-color:rgba(245,158,11,0.3)@endif">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="bell-dot"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Okunmamış</div>
            <div class="stat-card-value" style="@if($okunmamisCount > 0)color:var(--warning)@endif">{{ $okunmamisCount }}</div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Yeni bildirim</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Okunmuş</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $okunmusCount }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(139,92,246,0.18);color:#8b5cf6">
            <i data-lucide="calendar"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bugün</div>
            <div class="stat-card-value">{{ $bugun }}</div>
        </div>
    </div>
</div>

@if($bildirimler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="bell-off" class="empty-state-icon"></i>
            <h4>Bildirim yok 🎉</h4>
            <p>Yeni bildirimler burada listelenecek.</p>
        </div>
    </div>
@else
    {{-- Bildirim listesi - feed tarzı --}}
    <div class="section" style="padding:0;overflow:hidden">
        @foreach($bildirimler as $b)
            @php
                $isUnread = (int)($b->okundu ?? 0) === 0;
                $baslik = $b->baslik ?? 'Bildirim';
                $mesaj = $b->mesaj ?? $b->bildirim ?? '';
                $tip = $b->tip ?? 'system';
                $link = $b->link ?? null;

                // Tip → renk + ikon
                $tipConfig = [
                    'admin'    => ['ico' => 'shield', 'bg' => 'rgba(184,182,46,0.18)', 'color' => 'var(--brand)'],
                    'bayi'     => ['ico' => 'briefcase', 'bg' => 'rgba(59,130,246,0.18)', 'color' => '#3b82f6'],
                    'success'  => ['ico' => 'check-circle', 'bg' => 'rgba(16,185,129,0.18)', 'color' => '#10b981'],
                    'warning'  => ['ico' => 'alert-triangle', 'bg' => 'rgba(245,158,11,0.18)', 'color' => '#f59e0b'],
                    'error'    => ['ico' => 'alert-circle', 'bg' => 'rgba(239,68,68,0.18)', 'color' => '#ef4444'],
                    'info'     => ['ico' => 'info', 'bg' => 'rgba(59,130,246,0.18)', 'color' => '#3b82f6'],
                    'mesaj'    => ['ico' => 'message-square', 'bg' => 'rgba(139,92,246,0.18)', 'color' => '#8b5cf6'],
                    'fatura'   => ['ico' => 'file-text', 'bg' => 'rgba(245,158,11,0.18)', 'color' => '#f59e0b'],
                    'odeme'    => ['ico' => 'banknote', 'bg' => 'rgba(16,185,129,0.18)', 'color' => '#10b981'],
                    'system'   => ['ico' => 'bell', 'bg' => 'rgba(148,163,184,0.18)', 'color' => '#64748b'],
                ];
                $cfg = $tipConfig[$tip] ?? $tipConfig['system'];

                // Güvenli tarih
                $tarihHumans = null;
                $tarihFmt = null;
                $tarihVal = $b->created_at ?? $b->tarih ?? null;
                if (!empty($tarihVal)) {
                    try {
                        $tc = \Carbon\Carbon::parse($tarihVal);
                        $tarihHumans = $tc->diffForHumans();
                        $tarihFmt = $tc->format('d.m.Y H:i');
                    } catch (\Throwable $e) {}
                }
            @endphp
            <div class="notif-row {{ $isUnread ? 'unread' : '' }}">
                <div class="notif-icon" style="background:{{ $cfg['bg'] }};color:{{ $cfg['color'] }}">
                    <i data-lucide="{{ $cfg['ico'] }}" style="width:20px;height:20px"></i>
                </div>

                <div class="notif-body">
                    <div class="notif-title">
                        @if($link)
                            <a href="{{ $link }}" style="color:inherit;text-decoration:none">{{ $baslik }}</a>
                        @else
                            {{ $baslik }}
                        @endif
                        @if($tip && $tip !== 'system')
                            <span class="badge badge-neutral" style="font-size:9.5px;padding:1px 6px;margin-left:4px;vertical-align:middle">{{ ucfirst($tip) }}</span>
                        @endif
                    </div>
                    @if(!empty($mesaj))
                        @if($link)
                            <a href="{{ $link }}" style="color:inherit;text-decoration:none"><div class="notif-message">{{ $mesaj }}</div></a>
                        @else
                            <div class="notif-message">{{ $mesaj }}</div>
                        @endif
                    @endif
                    <div class="notif-time">
                        @if($tarihHumans)
                            <span title="{{ $tarihFmt }}">⏱ {{ $tarihHumans }}</span>
                        @else
                            <span>—</span>
                        @endif
                        @if($link)
                            <span style="margin-left:8px">·</span>
                            <a href="{{ $link }}" style="color:var(--brand);text-decoration:none;font-weight:600">
                                <i data-lucide="external-link" style="width:11px;height:11px;display:inline-block;vertical-align:middle"></i>
                                Aç
                            </a>
                        @endif
                    </div>
                </div>

                <div class="notif-actions">
                    @if($isUnread)
                        <form action="{{ route('admin.bildirim.okundu', $b->id) }}" method="POST" style="margin:0" onclick="event.stopPropagation();">
                            @csrf
                            <button type="submit" class="table-action" style="color:var(--success)" title="Okundu olarak işaretle">
                                <i data-lucide="check"></i>
                            </button>
                        </form>
                    @endif

                    <form action="{{ route('admin.bildirim.sil', $b->id) }}" method="POST" onsubmit="return confirm('Bildirim silinsin mi?');" style="margin:0">
                        @csrf @method('DELETE')
                        <button type="submit" class="table-action" style="color:var(--danger)" title="Sil">
                            <i data-lucide="trash-2"></i>
                        </button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    @if($bildirimler->hasPages())
        <div style="margin-top:16px">
            {{ $bildirimler->withQueryString()->links() }}
        </div>
    @endif
@endif

@endsection