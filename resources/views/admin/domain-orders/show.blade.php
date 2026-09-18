@extends('admin._layout')

@section('title', 'Sipariş #' . ($order->id ?? ''))

@push('head')
<style>
    .domain-banner {
        background: linear-gradient(135deg, var(--brand-soft), rgba(184,182,46,0.04));
        border: 1px solid rgba(184,182,46,0.3);
        border-radius: var(--radius-lg);
        padding: 24px;
        margin-top: 16px;
        text-align: center;
    }
    .domain-banner .domain-name {
        font-family: 'SF Mono', 'Monaco', 'Consolas', monospace;
        font-size: 32px;
        font-weight: 800;
        color: var(--brand-dark);
        word-break: break-all;
    }
    .domain-banner .domain-sub {
        font-size: 13px;
        color: var(--text-muted);
        margin-top: 8px;
    }

    .quick-action-list {
        display: flex; flex-direction: column; gap: 8px;
    }
    .quick-action-list form { margin: 0; }
    .quick-action-list .btn { width: 100%; justify-content: center; }

    .api-section {
        background: rgba(59,130,246,0.04);
        border: 1px dashed rgba(59,130,246,0.3);
        border-radius: var(--radius-md);
        padding: 14px;
    }
</style>
@endpush

@section('content')

@php
    $user = $order->user ?? null;
    $musteriAd = $user
        ? trim(($user->ad ?? '') . ' ' . ($user->soyad ?? ''))
        : 'Müşteri Bulunamadı';
    $musteriAd = $musteriAd ?: 'Müşteri Bulunamadı';
    $musteriEmail = $user->email ?? null;
    $musteriTel = $user->telefon ?? null;
    $harf = mb_strtoupper(mb_substr($musteriAd ?: '?', 0, 1, 'UTF-8'), 'UTF-8');

    // Tarih formatlama
    $tarihFmt = null;
    $tarihHuman = null;
    try {
        if ($order->created_at) {
            $tCarbon = \Carbon\Carbon::parse($order->created_at);
            $tarihFmt = $tCarbon->format('d.m.Y H:i');
            $tarihHuman = $tCarbon->locale('tr')->diffForHumans();
        }
    } catch (\Throwable $e) {}

    $kayitFmt = null;
    try {
        if (!empty($order->registered_at)) $kayitFmt = \Carbon\Carbon::parse($order->registered_at)->format('d.m.Y');
    } catch (\Throwable $e) {}

    $bitisFmt = null;
    $kalanGun = null;
    try {
        if (!empty($order->expires_at)) {
            $bitisCarbon = \Carbon\Carbon::parse($order->expires_at);
            $bitisFmt = $bitisCarbon->format('d.m.Y');
            $kalanGun = (int) floor(now()->diffInDays($bitisCarbon, false));
        }
    } catch (\Throwable $e) {}

    // Status konfigürasyonu
    $st = $order->status ?? 'pending';
    $statusCfg = match($st) {
        'pending'  => ['cls' => 'badge-warning', 'ic' => 'clock',       'txt' => 'Beklemede',   'desc' => 'Ödeme bekleniyor'],
        'paid'     => ['cls' => 'badge-brand',   'ic' => 'credit-card', 'txt' => 'Ödendi',      'desc' => 'Tescil işlemi başlatıldı'],
        'active'   => ['cls' => 'badge-success', 'ic' => 'check',       'txt' => 'Aktif',       'desc' => 'Domain başarıyla tescil edildi'],
        'failed'   => ['cls' => 'badge-danger',  'ic' => 'x',           'txt' => 'Başarısız',   'desc' => 'Manuel müdahale gerekli'],
        default    => ['cls' => 'badge-neutral', 'ic' => 'circle',      'txt' => $st,           'desc' => '—'],
    };
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.domain-orders.index') }}">Domain Siparişleri</a>
    <span class="sep">/</span>
    <span class="current">#{{ $order->id }}</span>
</div>

{{-- PROFILE HEADER --}}
<div class="profile-header">
    <div class="profile-avatar"
         style="background:linear-gradient(135deg,#b8b62e,#8a8a1f);font-size:30px;font-weight:700;color:#000">
        {{ $harf }}
    </div>
    <div class="profile-info">
        <h1 class="profile-name">{{ $musteriAd }}</h1>
        <div class="profile-meta">
            @if($musteriEmail)
                <span class="meta-item">
                    <i data-lucide="mail"></i>
                    <a href="mailto:{{ $musteriEmail }}" style="color:inherit;text-decoration:none">{{ $musteriEmail }}</a>
                </span>
            @endif
            @if($musteriTel)
                <span class="meta-item">
                    <i data-lucide="phone"></i>
                    {{ $musteriTel }}
                </span>
            @endif
            @if($tarihFmt)
                <span class="meta-item">
                    <i data-lucide="calendar"></i>
                    {{ $tarihFmt }}
                    @if($tarihHuman)
                        <span style="color:var(--text-muted);font-size:11px">({{ $tarihHuman }})</span>
                    @endif
                </span>
            @endif
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <span class="badge {{ $statusCfg['cls'] }}">
                <i data-lucide="{{ $statusCfg['ic'] }}" style="width:11px;height:11px"></i>
                {{ $statusCfg['txt'] }}
            </span>
            <span class="badge badge-neutral">
                <i data-lucide="hash" style="width:11px;height:11px"></i>
                {{ $order->id }}
            </span>
            @if(!empty($order->reseller_order_id))
                <span class="badge badge-brand">
                    <i data-lucide="link" style="width:11px;height:11px"></i>
                    RC: {{ $order->reseller_order_id }}
                </span>
            @endif
        </div>
    </div>
    <div class="profile-actions" style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('admin.domain-orders.index') }}" class="btn btn-secondary btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Listeye Dön</span>
        </a>
    </div>
</div>

{{-- DOMAIN BANNER --}}
<div class="domain-banner">
    <div class="domain-name">{{ $order->domain ?? '—' }}</div>
    <div class="domain-sub">{{ $statusCfg['desc'] }}</div>
</div>

{{-- 2 KOLON LAYOUT --}}
<div class="form-grid" style="margin-top:20px;grid-template-columns:2fr 1fr">

    {{-- SOL: Sipariş ve müşteri detayları --}}
    <div>
        {{-- SİPARİŞ BİLGİLERİ --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="package"></i>
                <span>Sipariş Bilgileri</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Sipariş ID</span>
                    <span class="val" style="font-family:monospace">#{{ $order->id }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Durum</span>
                    <span class="val">{{ $statusCfg['txt'] }}</span>
                </div>
                <div class="info-item full">
                    <span class="lbl">Domain</span>
                    <span class="val" style="font-family:monospace;color:var(--brand-dark);font-weight:700">
                        {{ $order->domain ?? '—' }}
                    </span>
                </div>
                @if(!empty($order->reseller_order_id))
                    <div class="info-item full">
                        <span class="lbl">ResellerClub Order ID</span>
                        <span class="val" style="font-family:monospace;font-size:12.5px">
                            {{ $order->reseller_order_id }}
                        </span>
                    </div>
                @endif
                @if(isset($order->price))
                    <div class="info-item">
                        <span class="lbl">Tutar</span>
                        <span class="val" style="color:var(--success);font-weight:700">
                            ₺{{ number_format((float)$order->price, 2, ',', '.') }}
                        </span>
                    </div>
                @endif
                @if(isset($order->years) && $order->years > 0)
                    <div class="info-item">
                        <span class="lbl">Süre</span>
                        <span class="val">{{ $order->years }} yıl</span>
                    </div>
                @endif
                <div class="info-item">
                    <span class="lbl">Sipariş Tarihi</span>
                    <span class="val">{{ $tarihFmt ?? '—' }}</span>
                </div>
                @if($kayitFmt)
                    <div class="info-item">
                        <span class="lbl">Tescil Tarihi</span>
                        <span class="val">{{ $kayitFmt }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- BİTİŞ / KALAN GÜN --}}
        @if($bitisFmt)
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="clock"></i>
                    <span>Süre Bilgisi</span>
                    @if($kalanGun !== null)
                        @if($kalanGun < 0)
                            <span class="badge badge-danger" style="margin-left:auto">
                                <i data-lucide="x-circle" style="width:11px;height:11px"></i>
                                Süresi Doldu
                            </span>
                        @elseif($kalanGun <= 30)
                            <span class="badge badge-warning" style="margin-left:auto">
                                <i data-lucide="alert-triangle" style="width:11px;height:11px"></i>
                                Yakında Sona Eriyor
                            </span>
                        @else
                            <span class="badge badge-success" style="margin-left:auto">
                                <i data-lucide="check-circle" style="width:11px;height:11px"></i>
                                Aktif
                            </span>
                        @endif
                    @endif
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div style="padding:14px;background:var(--brand-soft);border:1px solid rgba(184,182,46,0.3);border-radius:var(--radius-md);text-align:center">
                        <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:600">
                            Yenileme Tarihi
                        </div>
                        <div style="font-size:18px;font-weight:700;color:var(--brand-dark);margin-top:6px">
                            {{ $bitisFmt }}
                        </div>
                    </div>

                    @if($kalanGun !== null)
                        <div style="padding:14px;border-radius:var(--radius-md);text-align:center;
                            @if($kalanGun < 0) background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.3);
                            @elseif($kalanGun <= 30) background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3);
                            @else background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.3);
                            @endif">
                            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:600">
                                {{ $kalanGun < 0 ? 'Doldu' : 'Kalan' }}
                            </div>
                            <div style="font-size:24px;font-weight:700;margin-top:6px;
                                @if($kalanGun < 0) color:var(--danger);
                                @elseif($kalanGun <= 30) color:var(--warning);
                                @else color:var(--success);
                                @endif">
                                {{ abs($kalanGun) }} gün
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- RESELLERCLUB DETAY (API'den geldiyse) --}}
        @if(!empty($details))
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="cloud"></i>
                    <span>ResellerClub Detayları</span>
                    <span style="margin-left:auto;font-size:11px;color:var(--text-muted);font-weight:500;text-transform:none;letter-spacing:0">
                        API'den canlı
                    </span>
                </div>
                <div class="api-section">
                    <div class="info-grid">
                        @if(isset($details['status']))
                            <div class="info-item">
                                <span class="lbl">API Durumu</span>
                                <span class="val">{{ $details['status'] }}</span>
                            </div>
                        @endif
                        @if(!empty($details['creation_date']))
                            <div class="info-item">
                                <span class="lbl">Oluşturma</span>
                                <span class="val">
                                    @php
                                        try { $cd = \Carbon\Carbon::parse($details['creation_date'])->format('d.m.Y H:i'); }
                                        catch (\Throwable $e) { $cd = $details['creation_date']; }
                                    @endphp
                                    {{ $cd }}
                                </span>
                            </div>
                        @endif
                        @if(!empty($details['expiry_date']))
                            <div class="info-item">
                                <span class="lbl">Bitiş (API)</span>
                                <span class="val">
                                    @php
                                        try { $ed = \Carbon\Carbon::parse($details['expiry_date'])->format('d.m.Y H:i'); }
                                        catch (\Throwable $e) { $ed = $details['expiry_date']; }
                                    @endphp
                                    {{ $ed }}
                                </span>
                            </div>
                        @endif
                        @if(!empty($details['message']))
                            <div class="info-item full">
                                <span class="lbl">Mesaj</span>
                                <span class="val" style="font-size:12.5px">{{ $details['message'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- FATURA --}}
        @if(!empty($order->fatura))
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="receipt"></i>
                    <span>Fatura Bilgisi</span>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="lbl">Fatura ID</span>
                        <span class="val">#{{ $order->fatura->id ?? '—' }}</span>
                    </div>
                    @if(isset($order->fatura->durum))
                        <div class="info-item">
                            <span class="lbl">Fatura Durumu</span>
                            <span class="val">{{ $order->fatura->durum }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- SAĞ: Aksiyonlar + müşteri --}}
    <div>
        {{-- HIZLI AKSİYONLAR --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="zap"></i>
                <span>Hızlı Aksiyonlar</span>
            </div>
            <div class="quick-action-list">
                {{-- Durum kontrol et --}}
                @if(!empty($order->reseller_order_id))
                    <form action="{{ route('admin.domain-orders.check-status', $order->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i data-lucide="refresh-cw"></i>
                            <span>Durumu Kontrol Et</span>
                        </button>
                    </form>
                @endif

                {{-- Tekrar dene (sadece paid/failed için) --}}
                @if(in_array($st, ['paid', 'failed']))
                    <form action="{{ route('admin.domain-orders.retry', $order->id) }}" method="POST"
                          onsubmit="return confirm('Domain kayıt işlemi tekrar başlatılsın mı?');">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--warning)">
                            <i data-lucide="rotate-cw"></i>
                            <span>Tekrar Dene</span>
                        </button>
                    </form>
                @endif

                {{-- Yenile (sadece active için) --}}
                @if($st === 'active' && !empty($order->reseller_order_id))
                    <form action="{{ route('admin.domain-orders.renew', $order->id) }}" method="POST"
                          onsubmit="return confirm('Domain yenileme işlemi başlatılsın mı?');">
                        @csrf
                        <input type="hidden" name="years" value="1">
                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--success)">
                            <i data-lucide="repeat"></i>
                            <span>1 Yıl Yenile</span>
                        </button>
                    </form>
                @endif

                @if(empty($order->reseller_order_id))
                    <div style="font-size:12px;color:var(--text-muted);padding:10px;text-align:center;background:var(--bg-subtle);border-radius:var(--radius-md)">
                        <i data-lucide="info" style="width:14px;height:14px;display:inline;vertical-align:middle"></i>
                        ResellerClub Order ID henüz oluşturulmamış
                    </div>
                @endif
            </div>
        </div>

        {{-- MÜŞTERİ --}}
        <div class="section" style="margin-top:14px">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Müşteri Bilgileri</span>
            </div>
            @if(!$user)
                <div class="alert alert-warning" style="margin:0;padding:12px;font-size:13px">
                    <i data-lucide="alert-triangle" style="width:14px;height:14px;display:inline;vertical-align:middle"></i>
                    Üye bilgisi bulunamadı
                </div>
            @else
                <div class="info-grid">
                    <div class="info-item full">
                        <span class="lbl">Ad Soyad</span>
                        <span class="val">{{ $musteriAd }}</span>
                    </div>
                    @if($musteriEmail)
                        <div class="info-item full">
                            <span class="lbl">E-posta</span>
                            <span class="val" style="font-size:12.5px">
                                <a href="mailto:{{ $musteriEmail }}" style="color:var(--brand-dark);text-decoration:none">
                                    {{ $musteriEmail }}
                                </a>
                            </span>
                        </div>
                    @endif
                    @if($musteriTel)
                        <div class="info-item full">
                            <span class="lbl">Telefon</span>
                            <span class="val">{{ $musteriTel }}</span>
                        </div>
                    @endif
                    @if(isset($user->id))
                        <div class="info-item">
                            <span class="lbl">Üye ID</span>
                            <span class="val">#{{ $user->id }}</span>
                        </div>
                    @endif
                </div>

                @if(isset($user->id) && Route::has('admin.uyeler.duzenle'))
                    <a href="{{ route('admin.uyeler.duzenle', $user->id) }}"
                       class="btn btn-secondary btn-sm" style="width:100%;margin-top:12px">
                        <i data-lucide="external-link"></i>
                        <span>Üye Profilini Aç</span>
                    </a>
                @endif
            @endif
        </div>
    </div>
</div>

@endsection