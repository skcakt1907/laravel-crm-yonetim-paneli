@extends('admin._layout')

@section('title', ($row->domain ?? '—') . ' — Domain Detay')

@section('content')

@php
    // Her iki kaynak (domain_orders / satilanlar) için ortak alan eşleme
    $isOrder = $kaynak === 'order';
    $domain = $row->domain ?? '—';
    $baslangic = $row->registered_at ?? $row->baslangic_tarih ?? null;
    $bitis = $row->expires_at ?? $row->bitis_tarih ?? null;
    // Gerçek satış tutarı: sıfırdan farklı ilk değer (manuel=tutar, online=price, eski kayıt=fiyat)
    $tutar = 0;
    foreach (['tutar', 'price', 'fiyat'] as $__tk) {
        if (isset($row->$__tk) && (float) $row->$__tk != 0) { $tutar = $row->$__tk; break; }
    }
    $yil = $row->years ?? $row->yil ?? null;
    $statusRaw = $row->status ?? $row->durum ?? '';

    // Status mapping
    $statusMap = [
        'active'   => ['label' => 'Aktif',     'class' => 'badge-success', 'icon' => '🟢'],
        'kayitli'  => ['label' => 'Kayıtlı',   'class' => 'badge-success', 'icon' => '🟢'],
        '1'        => ['label' => 'Aktif',     'class' => 'badge-success', 'icon' => '🟢'],
        1          => ['label' => 'Aktif',     'class' => 'badge-success', 'icon' => '🟢'],
        'pending'  => ['label' => 'Beklemede', 'class' => 'badge-warning', 'icon' => '⏳'],
        'paid'     => ['label' => 'Ödendi',    'class' => 'badge-warning', 'icon' => '💳'],
        'bekliyor' => ['label' => 'Bekliyor',  'class' => 'badge-warning', 'icon' => '⏳'],
        'failed'   => ['label' => 'Başarısız', 'class' => 'badge-danger',  'icon' => '❌'],
        'expired'  => ['label' => 'Süresi Doldu', 'class' => 'badge-danger', 'icon' => '⏰'],
        'hata'     => ['label' => 'Hata',      'class' => 'badge-danger',  'icon' => '❌'],
        '0'        => ['label' => 'Pasif',     'class' => 'badge-danger',  'icon' => '🔴'],
        0          => ['label' => 'Pasif',     'class' => 'badge-danger',  'icon' => '🔴'],
    ];
    $status = $statusMap[$statusRaw] ?? ['label' => $statusRaw ?: 'Bilinmiyor', 'class' => 'badge-neutral', 'icon' => '❓'];

    // Kalan gün
    $kalanGun = null;
    if ($bitis) {
        try { $kalanGun = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($bitis)->startOfDay(), false); } catch (\Throwable $e) {}
    }

    $yas = null;
    if ($baslangic) {
        try { $yas = (int) \Carbon\Carbon::parse($baslangic)->startOfDay()->diffInDays(now()->startOfDay()); } catch (\Throwable $e) {}
    }

    // Müşteri adı
    $musteriAdi = $row->uye_firma ?: trim(($row->uye_ad ?? '').' '.($row->uye_soyad ?? '')) ?: ($row->uye_email ?? '—');
@endphp

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.crm.domains.index') }}">Domain &amp; Hosting Takip</a>
    <span class="sep">/</span>
    <span class="current">{{ $domain }}</span>
</div>

{{-- Profile Header --}}
<div class="profile-header">
    <div class="profile-avatar" style="background:linear-gradient(135deg, #3b82f6, #1e40af); font-size:30px">
        🌐
    </div>
    <div class="profile-info">
        <h1 class="profile-name">{{ $domain }}</h1>
        <div class="profile-meta" style="display:flex;flex-wrap:wrap;align-items:center;gap:6px 18px;line-height:1.4">
            @if(!empty($row->uye_id ?? $row->uyeid ?? $row->user_id ?? null))
                <span class="meta-item" style="display:inline-flex;align-items:center;gap:6px">
                    <i data-lucide="user" style="width:15px;height:15px;flex-shrink:0"></i>
                    <a href="{{ url('/admin/uyeler?search='.urlencode($row->uye_email ?? '')) }}" style="color:inherit;text-decoration:none;font-weight:600">
                        {{ $musteriAdi }}
                    </a>
                </span>
            @endif
            @if(!empty($row->uye_email))
                <span class="meta-item" style="display:inline-flex;align-items:center;gap:6px">
                    <i data-lucide="mail" style="width:15px;height:15px;flex-shrink:0"></i>
                    <a href="mailto:{{ $row->uye_email }}" style="color:inherit;text-decoration:none">{{ $row->uye_email }}</a>
                </span>
            @endif
            @if(!empty($row->uye_telefon))
                <span class="meta-item" style="display:inline-flex;align-items:center;gap:6px">
                    <i data-lucide="phone" style="width:15px;height:15px;flex-shrink:0"></i>
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $row->uye_telefon) }}" style="color:inherit;text-decoration:none">{{ $row->uye_telefon }}</a>
                </span>
            @endif
            @if($baslangic)
                <span class="meta-item" style="display:inline-flex;align-items:center;gap:6px">
                    <i data-lucide="calendar-check" style="width:15px;height:15px;flex-shrink:0"></i>
                    Kayıt: {{ \Carbon\Carbon::parse($baslangic)->format('d.m.Y') }}
                </span>
            @endif
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px">
            <span class="badge {{ $status['class'] }}" style="font-size:12px;padding:5px 10px">
                {{ $status['icon'] }} {{ $status['label'] }}
            </span>
            <span class="badge {{ $isOrder ? 'badge-brand' : 'badge-neutral' }}" style="font-size:12px;padding:5px 10px">
                @if($isOrder)
                    🛒 Online Sipariş
                @else
                    ✋ Manuel Eklendi
                @endif
            </span>
            @if($kalanGun !== null)
                @if($kalanGun < 0)
                    <span class="badge badge-danger" style="font-size:12px;padding:5px 10px">
                        ⚠️ {{ abs($kalanGun) }} gün geçti
                    </span>
                @elseif($kalanGun <= 7)
                    <span class="badge badge-danger" style="font-size:12px;padding:5px 10px">
                        🔥 {{ $kalanGun }} gün kaldı
                    </span>
                @elseif($kalanGun <= 30)
                    <span class="badge badge-warning" style="font-size:12px;padding:5px 10px">
                        ⏰ {{ $kalanGun }} gün kaldı
                    </span>
                @endif
            @endif
        </div>
    </div>
    <div class="profile-actions">
        <a href="https://{{ $domain }}" target="_blank" class="btn btn-secondary btn-sm" title="Domain'i yeni sekmede aç">
            <i data-lucide="external-link"></i>
            <span>Siteyi Aç</span>
        </a>
        @if(!$isOrder)
            <a href="{{ route('admin.crm.domains.edit', ['kaynak' => $kaynak, 'id' => $row->id]) }}" class="btn btn-primary btn-sm">
                <i data-lucide="edit-2"></i>
                <span>Düzenle</span>
            </a>
            <form action="{{ route('admin.crm.domains.destroy', ['kaynak' => $kaynak, 'id' => $row->id]) }}" method="POST" onsubmit="return confirm('Bu domain kaydını silmek istediğine emin misin?');" style="margin:0">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--danger)">
                    <i data-lucide="trash-2"></i>
                </button>
            </form>
        @else
            <div style="font-size:11px;color:var(--text-muted);text-align:right;max-width:160px">
                Online sipariş kaydı — buradan düzenlenmez. ResellerClub paneline bak.
            </div>
        @endif
    </div>
</div>

{{-- Kritik Uyarı Banner --}}
@if($kalanGun !== null && $kalanGun <= 30 && $kalanGun > -90)
    <div class="alert {{ $kalanGun < 0 ? 'alert-danger' : ($kalanGun <= 7 ? 'alert-danger' : 'alert-warning') }}" style="margin-top:16px;display:flex;align-items:center;gap:14px;padding:14px 18px">
        <div style="font-size:28px;line-height:1">
            @if($kalanGun < 0) ⚠️
            @elseif($kalanGun <= 7) 🔥
            @else ⏰
            @endif
        </div>
        <div style="flex:1">
            <div style="font-weight:700;margin-bottom:2px">
                @if($kalanGun < 0)
                    Domain Süresi {{ abs($kalanGun) }} Gün Önce Doldu!
                @elseif($kalanGun == 0)
                    Domain Bugün Bitiyor!
                @elseif($kalanGun <= 7)
                    Domain {{ $kalanGun }} Gün İçinde Bitecek!
                @else
                    Domain {{ $kalanGun }} Gün İçinde Bitecek
                @endif
            </div>
            <div style="font-size:12.5px;opacity:0.85">
                Bitiş tarihi: <strong>{{ $bitis ? \Carbon\Carbon::parse($bitis)->format('d.m.Y') : '—' }}</strong>
                @if($kalanGun < 0)
                    — Yenileme yapılmadıysa domain kaybedilmiş olabilir.
                @else
                    — Müşteriyi bilgilendir ve yenileme teklifi gönder.
                @endif
            </div>
        </div>
        @if(!empty($row->uye_email))
            <a href="mailto:{{ $row->uye_email }}?subject=Domain Yenileme: {{ $domain }}" class="btn btn-primary btn-sm">
                <i data-lucide="mail"></i>
                <span>Mail Gönder</span>
            </a>
        @endif
    </div>
@endif

{{-- Mini Stat Grid --}}
<div class="mini-stat-grid" style="margin-top:16px">
    <div class="mini-stat {{ $kalanGun !== null && $kalanGun < 0 ? 'danger' : ($kalanGun !== null && $kalanGun <= 30 ? 'warning' : 'info') }}">
        <div class="mini-stat-icon">📅</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Kalan Süre</div>
            <div class="mini-stat-value" style="font-size:18px">
                @if($kalanGun === null)
                    —
                @elseif($kalanGun < 0)
                    {{ abs($kalanGun) }} gün geçti
                @elseif($kalanGun == 0)
                    Bugün biter
                @else
                    {{ $kalanGun }} gün
                @endif
            </div>
            <div class="mini-stat-sub">
                {{ $bitis ? \Carbon\Carbon::parse($bitis)->format('d.m.Y') : 'Tarih yok' }}
            </div>
        </div>
    </div>

    <div class="mini-stat info">
        <div class="mini-stat-icon">💰</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Tutar</div>
            <div class="mini-stat-value">₺{{ number_format((float) $tutar, 2, ',', '.') }}</div>
            <div class="mini-stat-sub">{{ $yil ? $yil.' yıllık' : 'Toplam' }}</div>
        </div>
    </div>

    <div class="mini-stat {{ $status['class'] === 'badge-success' ? 'success' : ($status['class'] === 'badge-danger' ? 'danger' : 'warning') }}">
        <div class="mini-stat-icon">{{ $status['icon'] }}</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Durum</div>
            <div class="mini-stat-value" style="font-size:16px">{{ $status['label'] }}</div>
            <div class="mini-stat-sub">{{ $isOrder ? 'Online sipariş' : 'Manuel kayıt' }}</div>
        </div>
    </div>

    <div class="mini-stat info">
        <div class="mini-stat-icon">⏱️</div>
        <div class="mini-stat-body">
            <div class="mini-stat-label">Domain Yaşı</div>
            <div class="mini-stat-value" style="font-size:18px">
                @if($yas === null)
                    —
                @elseif($yas < 30)
                    {{ $yas }} gün
                @elseif($yas < 365)
                    {{ floor($yas/30) }} ay
                @else
                    {{ number_format($yas/365, 1, ',', '.') }} yıl
                @endif
            </div>
            <div class="mini-stat-sub">
                {{ $baslangic ? 'Sistemde: '.\Carbon\Carbon::parse($baslangic)->format('d.m.Y') : 'Başlangıç yok' }}
            </div>
        </div>
    </div>
</div>

{{-- Detay kartları --}}
<div class="form-grid" style="margin-top:20px">
    {{-- SOL: Detaylar --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="info"></i>
                <span>Domain Bilgileri</span>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Alan Adı</span>
                    <span class="val" style="font-weight:700;color:var(--brand)">🌐 {{ $domain }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Kaynak</span>
                    <span class="val">
                        @if($isOrder)
                            <span class="badge badge-brand">🛒 Online Sipariş</span>
                        @else
                            <span class="badge badge-neutral">✋ Manuel Eklendi</span>
                        @endif
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Durum</span>
                    <span class="val">
                        <span class="badge {{ $status['class'] }}">{{ $status['icon'] }} {{ $status['label'] }}</span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Kayıt ID</span>
                    <span class="val">#{{ $row->id }}</span>
                </div>
                @if($isOrder && !empty($row->reseller_order_id ?? null))
                    <div class="info-item">
                        <span class="lbl">Sipariş ID</span>
                        <span class="val">{{ $row->reseller_order_id }}</span>
                    </div>
                @endif
                @if(!empty($row->saglayici ?? null))
                    <div class="info-item">
                        <span class="lbl">Sağlayıcı</span>
                        <span class="val">{{ $row->saglayici }}</span>
                    </div>
                @endif
                <div class="info-item">
                    <span class="lbl">Başlangıç Tarihi</span>
                    <span class="val">{{ $baslangic ? \Carbon\Carbon::parse($baslangic)->format('d.m.Y H:i') : '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Bitiş Tarihi</span>
                    <span class="val" style="{{ $kalanGun !== null && $kalanGun < 30 ? 'color:var(--danger);font-weight:700' : '' }}">
                        {{ $bitis ? \Carbon\Carbon::parse($bitis)->format('d.m.Y') : '—' }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Süre</span>
                    <span class="val">{{ $yil ? $yil.' yıl' : '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Tutar</span>
                    <span class="val" style="color:var(--brand);font-weight:700">
                        ₺{{ number_format((float) $tutar, 2, ',', '.') }}
                    </span>
                </div>
                @if(!empty($row->created_at))
                    <div class="info-item">
                        <span class="lbl">Sisteme Eklendi</span>
                        <span class="val">{{ \Carbon\Carbon::parse($row->created_at)->format('d.m.Y H:i') }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Notlar / Mesaj --}}
        @if(!empty($row->mesaj ?? null))
            <div class="section">
                <div class="section-title">
                    <i data-lucide="message-square"></i>
                    <span>Notlar</span>
                </div>
                <div style="white-space:pre-wrap;color:var(--text);line-height:1.7;font-size:14px">{{ $row->mesaj }}</div>
            </div>
        @endif
    </div>

    {{-- SAĞ: Müşteri Kartı --}}
    <div>
        @if(!empty($row->uye_id ?? $row->uyeid ?? $row->user_id ?? null))
            <div class="section">
                <div class="section-title">
                    <i data-lucide="user"></i>
                    <span>Müşteri Bilgisi</span>
                </div>

                <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-subtle);border-radius:var(--radius-md);margin-bottom:12px">
                    <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--brand),#8a8a1f);color:#000;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px;flex-shrink:0">
                        {{ strtoupper(mb_substr($musteriAdi, 0, 1, 'UTF-8')) }}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:700;font-size:14px;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $musteriAdi }}
                        </div>
                        @if(!empty($row->uye_email))
                            <div style="font-size:11.5px;color:var(--text-muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                {{ $row->uye_email }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="info-grid">
                    @if(!empty($row->uye_firma ?? null))
                        <div class="info-item">
                            <span class="lbl">Firma</span>
                            <span class="val">{{ $row->uye_firma }}</span>
                        </div>
                    @endif
                    @if(!empty($row->uye_ad ?? null))
                        <div class="info-item">
                            <span class="lbl">Ad Soyad</span>
                            <span class="val">{{ trim(($row->uye_ad ?? '').' '.($row->uye_soyad ?? '')) }}</span>
                        </div>
                    @endif
                    @if(!empty($row->uye_email))
                        <div class="info-item">
                            <span class="lbl">E-posta</span>
                            <span class="val">
                                <a href="mailto:{{ $row->uye_email }}" style="color:var(--brand);text-decoration:none">{{ $row->uye_email }}</a>
                            </span>
                        </div>
                    @endif
                    @if(!empty($row->uye_telefon))
                        <div class="info-item">
                            <span class="lbl">Telefon</span>
                            <span class="val">
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $row->uye_telefon) }}" style="color:var(--brand);text-decoration:none">{{ $row->uye_telefon }}</a>
                            </span>
                        </div>
                    @endif
                    <div class="info-item">
                        <span class="lbl">Üye ID</span>
                        <span class="val">#{{ $row->uye_id ?? $row->uyeid ?? $row->user_id ?? '—' }}</span>
                    </div>
                </div>

                <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                    <a href="{{ url('/admin/uyeler?search='.urlencode($row->uye_email ?? '')) }}" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">
                        <i data-lucide="external-link"></i>
                        <span>Üye Profili</span>
                    </a>
                    @if(!empty($row->uye_email))
                        <a href="mailto:{{ $row->uye_email }}" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">
                            <i data-lucide="mail"></i>
                            <span>Mail At</span>
                        </a>
                    @endif
                </div>
            </div>
        @else
            <div class="section">
                <div class="empty-state" style="padding:24px">
                    <i data-lucide="user-x" class="empty-state-icon"></i>
                    <p>Bu domain bir üye ile eşleşmemiş.</p>
                </div>
            </div>
        @endif

        {{-- Hızlı Aksiyon --}}
        <div class="section">
            <div class="section-title">
                <i data-lucide="zap"></i>
                <span>Hızlı İşlemler</span>
            </div>

            <div style="display:flex;flex-direction:column;gap:6px">
                <a href="https://{{ $domain }}" target="_blank" class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                    <i data-lucide="globe"></i>
                    <span>Domain'i Tarayıcıda Aç</span>
                </a>
                <a href="https://who.is/whois/{{ $domain }}" target="_blank" class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                    <i data-lucide="search"></i>
                    <span>WHOIS Sorgula</span>
                </a>
                <a href="https://www.google.com/search?q={{ urlencode($domain) }}" target="_blank" class="btn btn-secondary btn-sm" style="justify-content:flex-start">
                    <i data-lucide="search"></i>
                    <span>Google'da Ara</span>
                </a>
                @if(!$isOrder)
                    <a href="{{ route('admin.crm.domains.edit', ['kaynak' => $kaynak, 'id' => $row->id]) }}" class="btn btn-primary btn-sm" style="justify-content:flex-start;margin-top:6px">
                        <i data-lucide="edit-2"></i>
                        <span>Bu Kaydı Düzenle</span>
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection