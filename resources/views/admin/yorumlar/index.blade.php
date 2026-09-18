@extends('admin._layout')

@section('title', 'Yorumlar')

@push('head')
<style>
    .yorum-row {
        transition: background 0.15s;
    }
    .yorum-row.bekliyor {
        background: linear-gradient(90deg, rgba(245,158,11,0.06), transparent);
        border-left: 3px solid var(--warning);
    }
    .yorum-row.red {
        opacity: 0.65;
    }

    .yorum-text {
        font-size: 13.5px;
        color: var(--text);
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .yorum-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        color: #000;
        display: inline-flex;
        align-items: center; justify-content: center;
        font-weight: 700;
        font-size: 13px;
        flex-shrink: 0;
    }

    .filter-pills {
        display: flex;
        gap: 6px;
        flex-wrap: wrap;
    }
    .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 99px;
        font-size: 12.5px;
        font-weight: 600;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    .filter-pill:hover { border-color: var(--brand-medium); }
    .filter-pill.active {
        background: var(--brand);
        color: #000;
        border-color: var(--brand-dark);
    }
    .filter-pill .count-badge {
        background: rgba(0,0,0,0.15);
        padding: 1px 7px;
        border-radius: 99px;
        font-size: 11px;
        font-weight: 700;
    }
    .filter-pill.active .count-badge {
        background: rgba(0,0,0,0.2);
        color: #000;
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Yorumlar</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="message-circle"></i>
            Yorumlar
            <span class="badge badge-brand">{{ $yorumlar->total() }}</span>
        </h1>
        <div class="page-subtitle">Site yorumlarını moderasyon edin</div>
    </div>
</div>

@php
    $toplam   = $yorumlar->total();
    $bekleyen = $yorumlar->filter(fn($y) => (int)($y->durum ?? 0) === 0)->count();
    $onayli   = $yorumlar->filter(fn($y) => (int)($y->durum ?? 0) === 1)->count();
    $red      = $yorumlar->filter(fn($y) => (int)($y->durum ?? 0) === 2)->count();

    $currentFilter = request()->query('durum', 'all');
@endphp

{{-- STAT KARTLARI --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,0.15);color:#3b82f6">
            <i data-lucide="message-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Yorum</div>
            <div class="stat-card-value">{{ $toplam }}</div>
        </div>
    </div>

    <div class="stat-card" style="@if($bekleyen > 0)border-color:rgba(245,158,11,0.3)@endif">
        <div class="stat-card-icon" style="background:rgba(245,158,11,0.18);color:#f59e0b">
            <i data-lucide="clock"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Onay Bekleyen</div>
            <div class="stat-card-value" style="@if($bekleyen > 0)color:var(--warning)@endif">{{ $bekleyen }}</div>
            @if($bekleyen > 0)
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">Moderasyon gerekli</div>
            @endif
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,0.15);color:#10b981">
            <i data-lucide="check-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Onaylı</div>
            <div class="stat-card-value" style="color:var(--success)">{{ $onayli }}</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(239,68,68,0.15);color:#ef4444">
            <i data-lucide="x-circle"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Reddedilen</div>
            <div class="stat-card-value" style="color:var(--danger)">{{ $red }}</div>
        </div>
    </div>
</div>

{{-- FİLTRE PILLS --}}
<div class="section" style="padding:14px 16px;margin-bottom:16px">
    <div class="filter-pills">
        <a href="{{ route('admin.yorumlar.index') }}"
           class="filter-pill {{ $currentFilter === 'all' ? 'active' : '' }}">
            <i data-lucide="list" style="width:13px;height:13px"></i>
            <span>Tümü</span>
            <span class="count-badge">{{ $toplam }}</span>
        </a>
        <a href="{{ route('admin.yorumlar.index', ['durum' => 'bekleyen']) }}"
           class="filter-pill {{ $currentFilter === 'bekleyen' ? 'active' : '' }}">
            <i data-lucide="clock" style="width:13px;height:13px"></i>
            <span>Bekleyen</span>
            <span class="count-badge">{{ $bekleyen }}</span>
        </a>
        <a href="{{ route('admin.yorumlar.index', ['durum' => 'onayli']) }}"
           class="filter-pill {{ $currentFilter === 'onayli' ? 'active' : '' }}">
            <i data-lucide="check" style="width:13px;height:13px"></i>
            <span>Onaylı</span>
            <span class="count-badge">{{ $onayli }}</span>
        </a>
        <a href="{{ route('admin.yorumlar.index', ['durum' => 'red']) }}"
           class="filter-pill {{ $currentFilter === 'red' ? 'active' : '' }}">
            <i data-lucide="x" style="width:13px;height:13px"></i>
            <span>Reddedilen</span>
            <span class="count-badge">{{ $red }}</span>
        </a>
    </div>
</div>

{{-- TABLO --}}
@if($yorumlar->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="message-circle" class="empty-state-icon"></i>
            <h4>Henüz yorum yok</h4>
            <p>Siteye yorum yapıldıkça burada görünür ve moderasyon edebilirsin.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:60px">#</th>
                        <th style="width:200px">Kullanıcı</th>
                        <th style="width:96px">Puan</th>
                        <th>Yorum</th>
                        <th style="width:150px">Paket</th>
                        <th style="width:130px">Durum</th>
                        <th class="text-right" style="width:180px">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($yorumlar as $y)
                        @php
                            $durum = (int)($y->durum ?? 0);
                            $musteriAd = trim(($y->ad ?? '') . ' ' . ($y->soyad ?? '')) ?: 'Misafir';
                            $harf = mb_strtoupper(mb_substr($musteriAd, 0, 1, 'UTF-8'), 'UTF-8');
                            $mesaj = $y->yorum ?? $y->mesaj ?? '—';
                            $hedef = $y->hedef ?? null;

                            $rowClass = match($durum) {
                                0 => 'bekliyor',
                                2 => 'red',
                                default => '',
                            };

                            // Filtreleme (client-side görünüm)
                            $skip = false;
                            if ($currentFilter === 'bekleyen' && $durum !== 0) $skip = true;
                            if ($currentFilter === 'onayli' && $durum !== 1) $skip = true;
                            if ($currentFilter === 'red' && $durum !== 2) $skip = true;
                        @endphp
                        @if(!$skip)
                        <tr class="yorum-row {{ $rowClass }}">
                            <td style="color:var(--text-muted);font-size:12px;vertical-align:top;padding-top:14px">#{{ $y->id }}</td>
                            <td style="vertical-align:top;padding-top:12px">
                                <div style="display:flex;gap:10px;align-items:center">
                                    <div class="yorum-avatar">{{ $harf }}</div>
                                    <div style="min-width:0">
                                        <div style="font-weight:600;font-size:13px;color:var(--text)">
                                            {{ $musteriAd }}
                                        </div>
                                        @if(!empty($y->tarih))
                                            @php
                                                $tFmt = null;
                                                try { $tFmt = \Carbon\Carbon::parse($y->tarih)->diffForHumans(); }
                                                catch (\Throwable $e) {}
                                            @endphp
                                            @if($tFmt)
                                                <div style="font-size:11px;color:var(--text-muted)">{{ $tFmt }}</div>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td style="vertical-align:top;padding-top:14px">
                                @if(!empty($y->puan))
                                    <div style="white-space:nowrap">
                                        <span style="color:#e0b400;font-size:12px;letter-spacing:.5px">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i data-lucide="star" style="width:12px;height:12px;{{ $i <= (int) $y->puan ? 'fill:#e0b400;color:#e0b400' : 'color:#d4d4d4' }}"></i>
                                            @endfor
                                        </span>
                                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px">{{ (int) $y->puan }}/5</div>
                                    </div>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="yorum-text">{{ strip_tags($mesaj) }}</div>
                            </td>
                            <td style="vertical-align:top;padding-top:14px">
                                @if($hedef)
                                    <span style="font-size:11.5px;color:var(--text-secondary);background:var(--bg-subtle);padding:3px 8px;border-radius:6px;font-family:monospace">
                                        {{ \Illuminate\Support\Str::limit($hedef, 30) }}
                                    </span>
                                @else
                                    <span style="color:var(--text-muted);font-size:12px">—</span>
                                @endif
                            </td>
                            <td style="vertical-align:top;padding-top:14px">
                                @if($durum === 1)
                                    <span class="badge badge-success">
                                        <i data-lucide="check" style="width:11px;height:11px"></i>
                                        Onaylı
                                    </span>
                                @elseif($durum === 2)
                                    <span class="badge badge-danger">
                                        <i data-lucide="x" style="width:11px;height:11px"></i>
                                        Reddedildi
                                    </span>
                                @else
                                    <span class="badge badge-warning">
                                        <i data-lucide="clock" style="width:11px;height:11px"></i>
                                        Bekliyor
                                    </span>
                                @endif
                            </td>
                            <td class="text-right" style="vertical-align:top;padding-top:10px">
                                <div class="table-actions" style="justify-content:flex-end">
                                    @if($durum !== 1)
                                        <button type="button" class="table-action"
                                                style="color:var(--success)"
                                                onclick="yorumDurum({{ $y->id }}, 1)"
                                                title="Onayla">
                                            <i data-lucide="check"></i>
                                        </button>
                                    @endif

                                    @if($durum !== 2)
                                        <button type="button" class="table-action"
                                                style="color:var(--danger)"
                                                onclick="yorumDurum({{ $y->id }}, 2)"
                                                title="Reddet">
                                            <i data-lucide="x"></i>
                                        </button>
                                    @endif

                                    @if($durum !== 0)
                                        <button type="button" class="table-action"
                                                style="color:var(--warning)"
                                                onclick="yorumDurum({{ $y->id }}, 0)"
                                                title="Beklemeye al">
                                            <i data-lucide="clock"></i>
                                        </button>
                                    @endif

                                    <button type="button" class="table-action"
                                            style="color:var(--danger);border-left:1px solid var(--border);padding-left:10px;margin-left:4px"
                                            onclick="silYorum({{ $y->id }})"
                                            title="Sil">
                                        <i data-lucide="trash-2"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($yorumlar->hasPages())
        <div style="margin-top:16px">{{ $yorumlar->withQueryString()->links() }}</div>
    @endif

    {{-- DURUM FORMLARI (HTML iç içe form yasak) --}}
    @foreach($yorumlar as $y)
        @for($d = 0; $d <= 2; $d++)
            <form id="durum-yorum-{{ $y->id }}-{{ $d }}"
                  action="{{ route('admin.yorumlar.durum', [$y->id, $d]) }}"
                  method="POST" style="display:none">
                @csrf
            </form>
        @endfor
        <form id="del-yorum-{{ $y->id }}"
              action="{{ route('admin.yorumlar.sil', $y->id) }}"
              method="POST" style="display:none">
            @csrf
            @method('DELETE')
        </form>
    @endforeach
@endif

<script>
function yorumDurum(id, durum) {
    const durumAdlari = {0: 'Beklemeye al', 1: 'Onayla', 2: 'Reddet'};
    if (confirm(durumAdlari[durum] + ' istediğine emin misin?')) {
        document.getElementById('durum-yorum-' + id + '-' + durum).submit();
    }
}

function silYorum(id) {
    if (confirm('Bu yorumu silmek istediğine emin misin?\n\nBu işlem geri alınamaz.')) {
        document.getElementById('del-yorum-' + id).submit();
    }
}
</script>

@endsection