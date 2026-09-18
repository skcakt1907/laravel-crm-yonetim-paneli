@extends('admin._layout')

@section('title', 'Yöneticiler')

@push('head')
<style>
    .y-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        color: #fff;
        font-weight: 700;
        font-size: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .y-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .y-name {
        font-weight: 600;
        font-size: 14px;
    }
    .y-uname {
        font-size: 11px;
        color: var(--text-muted);
        font-family: 'JetBrains Mono', monospace;
    }
    .rol-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 11px;
    }
</style>
@endpush

@section('content')

@php
    $rolRenkMap = [
        'primary'   => ['bg' => 'rgba(184,182,46,.15)',  'color' => '#8a8a1f'],
        'success'   => ['bg' => 'rgba(16,185,129,.15)',  'color' => '#10b981'],
        'danger'    => ['bg' => 'rgba(239,68,68,.15)',   'color' => '#ef4444'],
        'warning'   => ['bg' => 'rgba(245,158,11,.15)',  'color' => '#f59e0b'],
        'info'      => ['bg' => 'rgba(59,130,246,.15)',  'color' => '#3b82f6'],
        'dark'      => ['bg' => 'rgba(31,41,55,.15)',    'color' => '#1f2937'],
        'secondary' => ['bg' => 'rgba(107,114,128,.15)', 'color' => '#6b7280'],
    ];
    $aktifSayi = $yoneticiler->where('durum', 1)->count();
    $pasifSayi = $yoneticiler->where('durum', 0)->count();
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <span class="current">Yöneticiler</span>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="shield-check"></i>
            Yöneticiler
            <span class="badge badge-brand">{{ $yoneticiler->total() }}</span>
        </h1>
        <div class="page-subtitle">Sistem yöneticilerini ve rollerini yönet</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.roller.index') }}" class="btn btn-secondary">
            <i data-lucide="key-round"></i>
            <span>Roller</span>
        </a>
        <a href="{{ route('admin.yoneticiler.ekle') }}" class="btn btn-primary">
            <i data-lucide="user-plus"></i>
            <span>Yeni Yönetici</span>
        </a>
    </div>
</div>

{{-- STAT KARTLARI --}}
<div class="stat-grid" style="margin-bottom:20px">
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(184,182,46,.12);color:var(--brand-dark)">
            <i data-lucide="users"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Toplam Yönetici</div>
            <div class="stat-card-value">{{ $yoneticiler->total() }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(16,185,129,.12);color:#10b981">
            <i data-lucide="user-check"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Aktif</div>
            <div class="stat-card-value">{{ $aktifSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(239,68,68,.12);color:#ef4444">
            <i data-lucide="user-x"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Pasif</div>
            <div class="stat-card-value">{{ $pasifSayi }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon" style="background:rgba(59,130,246,.12);color:#3b82f6">
            <i data-lucide="key-round"></i>
        </div>
        <div class="stat-card-body">
            <div class="stat-card-label">Tanımlı Rol</div>
            <div class="stat-card-value">{{ count($rolHaritasi ?? []) }}</div>
        </div>
    </div>
</div>

{{-- TABLO --}}
@if($yoneticiler->isEmpty())
    <div class="section">
        <div class="empty-state">
            <i data-lucide="shield-off" class="empty-state-icon"></i>
            <h4>Henüz yönetici yok</h4>
            <p>İlk yöneticiyi eklemek için yukarıdaki butonu kullanın.</p>
        </div>
    </div>
@else
    <div class="table-wrap">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Yönetici</th>
                        <th>İletişim</th>
                        <th>Rol</th>
                        <th>Yetki</th>
                        <th>Durum</th>
                        <th>Son Giriş</th>
                        <th style="text-align:right">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($yoneticiler as $y)
                    @php
                        $isim = $y->adi ?? $y->kullaniciadi ?? '—';
                        $bas = strtoupper(mb_substr($isim, 0, 1));
                        $mail = $y->email ?? $y->eposta ?? null;
                        $tel = $y->telefon ?? null;
                        $rolObj = $rolHaritasi[$y->rol] ?? null;
                        $rolRenk = $rolObj && isset($rolRenkMap[$rolObj->renk]) ? $rolRenkMap[$rolObj->renk] : $rolRenkMap['primary'];
                        $yetkiVal = (int)($y->yetki ?? 1);
                        $yetkiText = ['1' => 'Standart', '2' => 'Gelişmiş', '3' => 'Tam'][$yetkiVal] ?? 'Standart';
                        $sonGiris = '—';
                        if (!empty($y->son_giris)) {
                            try {
                                $sonGiris = is_numeric($y->son_giris)
                                    ? \Carbon\Carbon::createFromTimestamp((int)$y->son_giris)->format('d.m.Y H:i')
                                    : \Carbon\Carbon::parse($y->son_giris)->format('d.m.Y H:i');
                            } catch (\Throwable $e) {}
                        }
                        $isMe = (int)session('admin_id') === (int)$y->id;
                    @endphp
                    <tr>
                        <td>
                            <div class="y-cell">
                                <div class="y-avatar">{{ $bas ?: 'A' }}</div>
                                <div>
                                    <div class="y-name">
                                        {{ $isim }}
                                        @if($isMe)<span class="badge badge-brand" style="font-size:9px;padding:2px 6px;margin-left:4px">Siz</span>@endif
                                    </div>
                                    <div class="y-uname">{{ '@' . ($y->kullaniciadi ?? '—') }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div style="font-size:13px">{{ $mail ?: '—' }}</div>
                            @if($tel)
                                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">{{ $tel }}</div>
                            @endif
                        </td>
                        <td>
                            @if($rolObj)
                                <span class="rol-badge" style="background:{{ $rolRenk['bg'] }};color:{{ $rolRenk['color'] }}">
                                    <span>{{ $rolObj->ikon ?? '🎭' }}</span>
                                    <span>{{ $rolObj->ad }}</span>
                                </span>
                            @else
                                <span class="badge badge-neutral">Rol #{{ $y->rol }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-neutral">{{ $yetkiText }}</span>
                        </td>
                        <td>
                            @if((int)($y->durum ?? 0) === 1)
                                <span class="badge badge-success">Aktif</span>
                            @else
                                <span class="badge badge-danger">Pasif</span>
                            @endif
                        </td>
                        <td>
                            <span style="font-size:12px;color:var(--text-muted)">{{ $sonGiris }}</span>
                        </td>
                        <td>
                            <div class="table-actions" style="justify-content:flex-end">
                                <a href="{{ route('admin.yoneticiler.duzenle', $y->id) }}"
                                   class="table-action" title="Düzenle">
                                    <i data-lucide="edit-3"></i>
                                </a>
                                @if(!$isMe)
                                <button type="button" class="table-action danger"
                                        onclick="document.getElementById('y-sil-{{ $y->id }}').submit()"
                                        title="Sil">
                                    <i data-lucide="trash-2"></i>
                                </button>
                                @endif
                            </div>
                            @if(!$isMe)
                            <form id="y-sil-{{ $y->id }}"
                                  action="{{ route('admin.yoneticiler.sil', $y->id) }}"
                                  method="POST" style="display:none"
                                  onsubmit="return confirm('Yönetici silinsin mi?')">
                                @csrf
                                @method('DELETE')
                            </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
        <div style="font-size:13px;color:var(--text-muted)">
            <strong>{{ $yoneticiler->firstItem() }}</strong> - <strong>{{ $yoneticiler->lastItem() }}</strong> arası,
            toplam <strong>{{ $yoneticiler->total() }}</strong> yönetici
        </div>
        <div>{{ $yoneticiler->links() }}</div>
    </div>
@endif

@endsection