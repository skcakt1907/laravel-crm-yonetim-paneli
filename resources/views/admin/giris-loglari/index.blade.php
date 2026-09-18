@extends('admin._layout')

@section('title', 'Giriş Logları')

@push('head')
<style>
    .log-ip {
        font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
        font-size: 12px;
        background: var(--bg-subtle);
        color: var(--text);
        padding: 3px 9px;
        border-radius: 6px;
        display: inline-block;
    }
    .log-avatar {
        width: 34px; height: 34px;
        border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 13px; color: #fff;
        flex-shrink: 0;
    }
    .tip-badge {
        font-size: 10px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .4px; padding: 2px 7px; border-radius: 5px;
    }
    .tip-yonetici { background: rgba(184,182,46,.18); color: var(--brand-dark); }
    .tip-uye { background: rgba(59,130,246,.14); color: #3b82f6; }
    .filter-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
    .filter-tab {
        padding: 9px 18px; border-radius: 10px; font-size: 13px; font-weight: 600;
        background: var(--bg-subtle); color: var(--text); border: 1px solid var(--border);
        cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
    }
    .filter-tab.active { background: var(--brand); color: #1f2419; border-color: var(--brand); }
    .filter-tab .cnt { font-size: 11px; opacity: .75; }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Giriş Logları</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="shield"></i>
            Giriş Logları
        </h1>
        <div class="page-subtitle">Sisteme yapılan tüm giriş denemelerini ve güvenlik olaylarını takip edin</div>
    </div>
</div>

@if(!empty($tableMissing))
    <div class="alert alert-warning" style="margin-bottom:20px;display:flex;align-items:flex-start;gap:12px">
        <i data-lucide="alert-triangle" style="width:20px;height:20px;flex-shrink:0;margin-top:2px"></i>
        <div>
            <strong>Giriş log tablosu bulunamadı.</strong>
            <div style="margin-top:6px;font-size:13px">
                <code style="background:rgba(0,0,0,.1);padding:1px 6px;border-radius:4px">giris_loglari</code>
                tablosunu oluşturmak için <code>03_giris_loglari.sql</code> dosyasını çalıştırın.
            </div>
        </div>
    </div>
@else

{{-- STAT KARTLARI --}}
<div class="stat-grid" style="margin-bottom:20px;grid-template-columns:repeat(4,1fr)">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Başarılı Giriş</div>
            <div class="stat-card-value">{{ number_format($istatistik['basarili'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(239,68,68,.14);color:#ef4444">
            <i data-lucide="x-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Başarısız Deneme</div>
            <div class="stat-card-value">{{ number_format($istatistik['basarisiz'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(245,158,11,.15);color:#f59e0b">
            <i data-lucide="ban"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Bloke Edilen</div>
            <div class="stat-card-value">{{ number_format($istatistik['bloke'], 0, ',', '.') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,.14);color:#3b82f6">
            <i data-lucide="globe"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Benzersiz IP</div>
            <div class="stat-card-value">{{ number_format($istatistik['tekil_ip'], 0, ',', '.') }}</div>
        </div>
    </div>
</div>

{{-- FİLTRELER --}}
<div class="filter-tabs">
    <a href="{{ request()->fullUrlWithQuery(['durum' => 'tumu', 'tip' => $tip, 'page' => null]) }}" class="filter-tab {{ $filtre === 'tumu' ? 'active' : '' }}">
        Tümü <span class="cnt">{{ $istatistik['toplam'] }}</span>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['durum' => 'basarili', 'tip' => $tip, 'page' => null]) }}" class="filter-tab {{ $filtre === 'basarili' ? 'active' : '' }}">
        <i data-lucide="check" style="width:13px;height:13px"></i> Başarılı <span class="cnt">{{ $istatistik['basarili'] }}</span>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['durum' => 'basarisiz', 'tip' => $tip, 'page' => null]) }}" class="filter-tab {{ $filtre === 'basarisiz' ? 'active' : '' }}">
        <i data-lucide="x" style="width:13px;height:13px"></i> Başarısız <span class="cnt">{{ $istatistik['basarisiz'] }}</span>
    </a>
    <a href="{{ request()->fullUrlWithQuery(['durum' => 'bloke', 'tip' => $tip, 'page' => null]) }}" class="filter-tab {{ $filtre === 'bloke' ? 'active' : '' }}">
        <i data-lucide="ban" style="width:13px;height:13px"></i> Bloke <span class="cnt">{{ $istatistik['bloke'] }}</span>
    </a>

    <span style="flex:1"></span>

    <a href="{{ request()->fullUrlWithQuery(['durum' => $filtre, 'tip' => 'tumu', 'page' => null]) }}" class="filter-tab {{ $tip === 'tumu' ? 'active' : '' }}">Herkes</a>
    <a href="{{ request()->fullUrlWithQuery(['durum' => $filtre, 'tip' => 'yonetici', 'page' => null]) }}" class="filter-tab {{ $tip === 'yonetici' ? 'active' : '' }}">
        <i data-lucide="user-cog" style="width:13px;height:13px"></i> Yöneticiler
    </a>
    <a href="{{ request()->fullUrlWithQuery(['durum' => $filtre, 'tip' => 'uye', 'page' => null]) }}" class="filter-tab {{ $tip === 'uye' ? 'active' : '' }}">
        <i data-lucide="users" style="width:13px;height:13px"></i> Üyeler
    </a>
</div>

{{-- TABLO --}}
@if($loglar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="shield-off" class="empty-state-icon"></i>
            <h4>Kayıt bulunamadı</h4>
            <p>Seçili filtrelerde giriş kaydı yok.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:160px">Tarih / Saat</th>
                        <th>Kullanıcı / E-posta</th>
                        <th style="width:140px">IP Adresi</th>
                        <th style="width:110px">Durum</th>
                        <th>Açıklama</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loglar as $log)
                        @php
                            $renkler = ['#b8b62e','#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6'];
                            $isim = $log->kullanici_adi ?: ($log->email ?: '?');
                            $bas = mb_strtoupper(mb_substr(trim($isim), 0, 1));
                            $renk = $renkler[ (crc32($isim) % count($renkler)) ];
                            $tarih = '—'; $saat = '';
                            if (!empty($log->created_at)) {
                                try {
                                    $c = \Carbon\Carbon::parse($log->created_at);
                                    $tarih = $c->format('d.m.Y'); $saat = $c->format('H:i:s');
                                } catch (\Throwable $e) {}
                            }
                        @endphp
                        <tr>
                            <td>
                                <div style="font-weight:600;font-size:13px">{{ $tarih }}</div>
                                <div style="font-size:12px;color:var(--text-muted)">{{ $saat }}</div>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <span class="log-avatar" style="background:{{ $renk }}">{{ $bas }}</span>
                                    <div style="min-width:0">
                                        <div style="font-weight:600;font-size:13px;display:flex;align-items:center;gap:6px">
                                            {{ $log->kullanici_adi ?: '—' }}
                                            <span class="tip-badge {{ $log->kullanici_tipi === 'yonetici' ? 'tip-yonetici' : 'tip-uye' }}">
                                                {{ $log->kullanici_tipi === 'yonetici' ? 'Yönetici' : 'Üye' }}
                                            </span>
                                        </div>
                                        <div style="font-size:12px;color:var(--text-muted);overflow:hidden;text-overflow:ellipsis">{{ $log->email ?: '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="log-ip">{{ $log->ip ?: '—' }}</span></td>
                            <td>
                                @if($log->durum === 'basarili')
                                    <span class="badge badge-success"><i data-lucide="check" style="width:11px;height:11px"></i> Başarılı</span>
                                @elseif($log->durum === 'bloke')
                                    <span class="badge" style="background:rgba(245,158,11,.15);color:#f59e0b"><i data-lucide="ban" style="width:11px;height:11px"></i> Bloke</span>
                                @else
                                    <span class="badge" style="background:rgba(239,68,68,.14);color:#ef4444"><i data-lucide="x" style="width:11px;height:11px"></i> Başarısız</span>
                                @endif
                            </td>
                            <td><span style="font-size:13px;color:var(--text-muted)">{{ $log->aciklama ?: '—' }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($loglar->hasPages())
            <div style="padding:14px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
                <div style="font-size:13px;color:var(--text-muted)">
                    {{ $loglar->firstItem() ?? 0 }} – {{ $loglar->lastItem() ?? 0 }} / Toplam
                    <strong style="color:var(--text)">{{ $loglar->total() }}</strong>
                </div>
                {{ $loglar->links() }}
            </div>
        @endif
    </div>
@endif

@endif

@endsection