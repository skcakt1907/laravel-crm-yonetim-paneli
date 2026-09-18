@extends('admin._layout')

@section('title', 'Teklif #' . ($teklif->id ?? ''))

@push('head')
<style>
    .teklif-toplam {
        padding: 16px 20px;
        background: var(--brand-soft);
        border: 1px solid rgba(184,182,46,0.3);
        border-radius: var(--radius-md);
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 16px;
    }
    .teklif-toplam .lbl { font-size: 13px; color: var(--text-secondary); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .teklif-toplam .val {
        font-size: 28px; font-weight: 700;
        color: var(--brand-dark);
    }

    .ek-dosya-box {
        display: flex; align-items: center; gap: 14px;
        padding: 14px;
        background: rgba(16,185,129,0.06);
        border: 1px solid rgba(16,185,129,0.25);
        border-radius: var(--radius-md);
    }
    .ek-dosya-icon {
        width: 44px; height: 44px;
        border-radius: 50%;
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
        display: inline-flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
</style>
@endpush

@section('content')

@php
    // Tarih formatlama (güvenli)
    $tarihFmt = null;
    if (!empty($teklif->created_at)) {
        try { $tarihFmt = \Carbon\Carbon::parse($teklif->created_at)->format('d.m.Y H:i'); }
        catch (\Throwable $e) {}
    }

    // Durum badge
    $durumCfg = match($teklif->durum ?? '') {
        'onaylandi'  => ['cls' => 'badge-success',  'ic' => 'check',  'txt' => 'Onaylandı'],
        'reddedildi' => ['cls' => 'badge-danger',   'ic' => 'x',      'txt' => 'Reddedildi'],
        default      => ['cls' => 'badge-warning',  'ic' => 'clock',  'txt' => 'Beklemede'],
    };

    $musteriAd = trim(($teklif->ad ?? '') . ' ' . ($teklif->soyad ?? '')) ?: 'Müşteri';
    $harf = mb_strtoupper(mb_substr($teklif->ad ?? '?', 0, 1, 'UTF-8'), 'UTF-8');
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.paketler.index') }}">Paketler</a>
    <span class="sep">/</span>
    <span class="current">Teklif #{{ $teklif->id }}</span>
</div>

{{-- PROFILE HEADER --}}
<div class="profile-header">
    <div class="profile-avatar"
         style="background:linear-gradient(135deg,#b8b62e,#8a8a1f);font-size:30px;font-weight:700;color:#000">
        {{ $harf }}
    </div>
    <div class="profile-info">
        <h1 class="profile-name">{{ $teklif->baslik ?? 'Paket Teklifi' }}</h1>
        <div class="profile-meta">
            <span class="meta-item">
                <i data-lucide="user"></i>
                {{ $musteriAd }}
            </span>
            @if(!empty($teklif->email))
                <span class="meta-item">
                    <i data-lucide="mail"></i>
                    <a href="mailto:{{ $teklif->email }}" style="color:inherit;text-decoration:none">{{ $teklif->email }}</a>
                </span>
            @endif
            @if($tarihFmt)
                <span class="meta-item">
                    <i data-lucide="calendar"></i>
                    {{ $tarihFmt }}
                </span>
            @endif
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
            <span class="badge {{ $durumCfg['cls'] }}">
                <i data-lucide="{{ $durumCfg['ic'] }}" style="width:11px;height:11px"></i>
                {{ $durumCfg['txt'] }}
            </span>
            <span class="badge badge-neutral">
                <i data-lucide="hash" style="width:11px;height:11px"></i>
                {{ $teklif->id }}
            </span>
            @if(!empty($teklif->para_birimi))
                <span class="badge badge-brand">
                    <i data-lucide="banknote" style="width:11px;height:11px"></i>
                    {{ $teklif->para_birimi }}
                </span>
            @endif
        </div>
    </div>
    <div class="profile-actions" style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('admin.paketler.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Geri</span>
        </a>
        <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
            <i data-lucide="printer"></i>
            <span>Yazdır</span>
        </button>
    </div>
</div>

{{-- PAKETLER --}}
<div class="section" style="margin-top:20px;padding:0;overflow:hidden">
    <div style="padding:16px 16px 0">
        <div class="section-title" style="border-bottom:none;padding-bottom:0;margin-bottom:0">
            <i data-lucide="package"></i>
            <span>Paketler</span>
            <span class="badge badge-neutral" style="margin-left:auto;font-size:11px">
                {{ count($paketler ?? []) }} adet
            </span>
        </div>
    </div>

    <div class="table-wrap" style="border:none;margin-top:12px">
        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Paket</th>
                        <th style="text-align:center;width:90px">Süre</th>
                        <th style="text-align:right;width:140px">Birim Fiyat</th>
                        <th style="text-align:right;width:160px">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paketler ?? [] as $p)
                        <tr>
                            <td style="font-weight:600">{{ $p->adi ?? '—' }}</td>
                            <td style="text-align:center">{{ ($p->adet ?? 1) }} ay</td>
                            <td style="text-align:right">
                                ₺{{ number_format($p->birim_fiyat_tl ?? 0, 2, ',', '.') }}
                            </td>
                            <td style="text-align:right;font-weight:700;color:var(--brand-dark)">
                                ₺{{ number_format($p->satir_toplam_tl ?? 0, 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- TOPLAM --}}
        <div class="teklif-toplam">
            <div>
                <div class="lbl">Genel Toplam</div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                    KDV dahil değildir
                </div>
            </div>
            <div class="val">
                ₺{{ number_format($teklif->toplam_tl ?? 0, 2, ',', '.') }}
            </div>
        </div>
    </div>
</div>

{{-- AÇIKLAMA --}}
@if(!empty($teklif->aciklama))
    <div class="section" style="margin-top:16px">
        <div class="section-title">
            <i data-lucide="file-text"></i>
            <span>Açıklama</span>
        </div>
        @php
            $aciklamaHam = $teklif->aciklama;
            // HTML etiketi içeriyor mu? (TinyMCE çıktısı) — yalnız güvenli etiketlere izin ver (XSS koruması)
            $htmlMi = $aciklamaHam !== strip_tags($aciklamaHam);
            $izinli = '<p><br><b><strong><i><em><u><s><ul><ol><li><h1><h2><h3><h4><span><a><blockquote><table><thead><tbody><tr><td><th>';
            $aciklamaGuvenli = $htmlMi ? strip_tags($aciklamaHam, $izinli) : null;
        @endphp
        @if($htmlMi)
            <div class="teklif-aciklama-html" style="line-height:1.7;font-size:14px;color:var(--text-secondary)">{!! $aciklamaGuvenli !!}</div>
        @else
            <div style="white-space:pre-wrap;line-height:1.7;font-size:14px;color:var(--text-secondary)">{{ $aciklamaHam }}</div>
        @endif
    </div>
@endif

{{-- EK DOSYA --}}
@if(!empty($teklif->dosya))
    <div class="section" style="margin-top:16px">
        <div class="section-title">
            <i data-lucide="paperclip"></i>
            <span>Ek Dosya</span>
        </div>
        <div class="ek-dosya-box">
            <div class="ek-dosya-icon">
                <i data-lucide="file" style="width:22px;height:22px"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:14px">Teklif Dosyası</div>
                <div style="font-size:12px;color:var(--text-muted);margin-top:2px;font-family:monospace;word-break:break-all">
                    {{ basename($teklif->dosya) }}
                </div>
            </div>
            <a href="{{ asset('storage/' . $teklif->dosya) }}" target="_blank"
               class="btn btn-primary btn-sm">
                <i data-lucide="download"></i>
                <span>İndir</span>
            </a>
        </div>
    </div>
@endif

<style>
@media print {
    aside, .breadcrumb, .page-header .page-actions, .profile-actions { display: none !important; }
    .page-header, .profile-header, .section, .table-wrap {
        box-shadow: none !important;
        page-break-inside: avoid;
    }
}
</style>

@endsection