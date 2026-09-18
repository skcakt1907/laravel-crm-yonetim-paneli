@extends('admin._layout')

@section('title', 'Satış Detayı #' . ($satis->id ?? ''))

@push('head')
<style>
    .urun-chip {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 4px 10px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        border-radius: 6px;
        font-weight: 600;
        font-size: 12.5px;
    }

    .info-box-big {
        background: linear-gradient(135deg, var(--brand-soft), rgba(184,182,46,0.04));
        border: 1px solid rgba(184,182,46,0.25);
        border-radius: var(--radius-lg);
        padding: 20px;
        margin-top: 16px;
    }
    .info-box-big .lbl {
        font-size: 11px;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    .info-box-big .val {
        font-size: 32px;
        font-weight: 700;
        color: var(--brand-dark);
        margin-top: 8px;
    }

    /* Tip rozet (üst sağ) */
    .tip-banner {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 14px;
        border-radius: 99px;
        font-weight: 600;
        font-size: 13px;
    }
    .tip-banner.hosting { background: rgba(59,130,246,0.15); color: #3b82f6; }
    .tip-banner.web-paket { background: rgba(184,182,46,0.18); color: var(--brand-dark); }
    .tip-banner.domain { background: rgba(139,92,246,0.18); color: #8b5cf6; }
</style>
@endpush

@section('content')

@php
    $musteriAd = $satis->musteri_ad ?? trim(($satis->ad ?? '') . ' ' . ($satis->soyad ?? '')) ?: 'Müşteri Bulunamadı';
    $musteriEmail = $satis->musteri_email ?? $satis->email ?? null;
    $musteriTel = $satis->musteri_telefon ?? $satis->telefon ?? null;
    $harf = mb_strtoupper(mb_substr($musteriAd ?: '?', 0, 1, 'UTF-8'), 'UTF-8');

    $urun = $satis->urun ?? '—';
    $tutarVal = (float)($satis->tutar ?? 0);

    // Tarih
    $tFmt = null;
    $tHumanFmt = null;
    $rawT = $satis->tarih ?? $satis->created_at ?? null;
    if (!empty($rawT)) {
        try {
            $tCarbon = \Carbon\Carbon::parse($rawT);
            $tFmt = $tCarbon->format('d.m.Y H:i');
            $tHumanFmt = $tCarbon->locale('tr')->diffForHumans();
        } catch (\Throwable $e) {}
    }

    // Tip ayarı
    $tip = $tip ?? 'genel';
    $tipConfig = match($tip) {
        'hosting'   => ['ic' => 'server',  'name' => 'Hosting Satışı',   'list_route' => 'admin.satislar.hosting',   'list_name' => 'Hosting Satışları'],
        'web-paket' => ['ic' => 'package', 'name' => 'Web Paket Satışı', 'list_route' => 'admin.satislar.web-paket', 'list_name' => 'Web Paket Satışları'],
        'domain'    => ['ic' => 'globe',   'name' => 'Domain Satışı',    'list_route' => 'admin.satislar.domain',    'list_name' => 'Domain Satışları'],
        default     => ['ic' => 'shopping-cart', 'name' => 'Satış',     'list_route' => 'admin.dashboard',           'list_name' => 'Anasayfa'],
    };
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    @if(Route::has($tipConfig['list_route']))
        <a href="{{ route($tipConfig['list_route']) }}">{{ $tipConfig['list_name'] }}</a>
        <span class="sep">/</span>
    @endif
    <span class="current">Satış #{{ $satis->id }}</span>
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
            @if($tFmt)
                <span class="meta-item">
                    <i data-lucide="calendar"></i>
                    {{ $tFmt }}
                    @if($tHumanFmt)
                        <span style="color:var(--text-muted);font-size:11px">({{ $tHumanFmt }})</span>
                    @endif
                </span>
            @endif
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <span class="tip-banner {{ $tip }}">
                <i data-lucide="{{ $tipConfig['ic'] }}" style="width:14px;height:14px"></i>
                {{ $tipConfig['name'] }}
            </span>
            <span class="badge badge-neutral">
                <i data-lucide="hash" style="width:11px;height:11px"></i>
                {{ $satis->id }}
            </span>
            @if(!empty($satis->uyeid))
                <span class="badge badge-neutral">
                    <i data-lucide="user" style="width:11px;height:11px"></i>
                    Üye ID: {{ $satis->uyeid }}
                </span>
            @endif
        </div>
    </div>
    <div class="profile-actions" style="display:flex;gap:8px;flex-wrap:wrap">
        @if(Route::has($tipConfig['list_route']))
            <a href="{{ route($tipConfig['list_route']) }}" class="btn btn-secondary btn-sm">
                <i data-lucide="arrow-left"></i>
                <span>Listeye Dön</span>
            </a>
        @endif
        <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">
            <i data-lucide="printer"></i>
            <span>Yazdır</span>
        </button>
    </div>
</div>

{{-- TUTAR BÜYÜK BOX --}}
<div class="info-box-big">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
        <div>
            <div class="lbl">Satış Tutarı</div>
            <div class="val">₺{{ number_format($tutarVal, 2, ',', '.') }}</div>
        </div>
        <div style="text-align:right">
            <div class="lbl">Ürün / Paket</div>
            <div style="margin-top:8px">
                <span class="urun-chip" style="font-size:14px;padding:8px 14px">
                    <i data-lucide="{{ $tipConfig['ic'] }}" style="width:14px;height:14px"></i>
                    {{ $urun }}
                </span>
            </div>
        </div>
    </div>
</div>

{{-- DETAY ALANLARI --}}
@php
    // Süre/bitiş tarihi tespit et - tabloda hangi field varsa kullan
    $bitisTarihi = null;
    $bitisFmt = null;
    $bitisOtomatik = false;  // +1 yıl ile hesaplandıysa
    $kalanGun = null;
    $durum = null;  // 'aktif', 'sona-eriyor', 'bitti'

    // 1. Önce DB'deki olası bitiş field'larına bak
    $bitisFieldleri = ['bitis_tarihi', 'bitis', 'sona_erme', 'sona_erme_tarihi', 'expires_at', 'expire_date', 'son_tarih'];
    foreach ($bitisFieldleri as $bf) {
        if (!empty($satis->$bf ?? null)) {
            $bitisTarihi = $satis->$bf;
            break;
        }
    }

    // 2. Bulunamadıysa: tarih + 1 yıl ile hesapla (hosting/domain genelde yıllık)
    if (!$bitisTarihi && !empty($rawT) && in_array($tip, ['hosting', 'domain'])) {
        try {
            $bitisCarbon = \Carbon\Carbon::parse($rawT)->addYear();
            $bitisTarihi = $bitisCarbon->toDateTimeString();
            $bitisOtomatik = true;
        } catch (\Throwable $e) {}
    }

    // 3. Bitiş formatla + kalan gün hesapla
    if ($bitisTarihi) {
        try {
            $bitisCarbon = \Carbon\Carbon::parse($bitisTarihi);
            $bitisFmt = $bitisCarbon->format('d.m.Y');

            $now = now();
            $kalanGun = (int) floor($now->diffInDays($bitisCarbon, false));

            if ($kalanGun < 0)        $durum = 'bitti';
            elseif ($kalanGun <= 30)  $durum = 'sona-eriyor';
            else                       $durum = 'aktif';
        } catch (\Throwable $e) {}
    }

    // Başlangıç (satış tarihi) Carbon - önce DB field'ına bak
    $baslangicCarbon = null;
    $baslangicRaw = $satis->baslangic_tarihi ?? $rawT ?? null;
    if (!empty($baslangicRaw)) {
        try { $baslangicCarbon = \Carbon\Carbon::parse($baslangicRaw); }
        catch (\Throwable $e) {}
    }
@endphp

{{-- SÜRE BİLGİSİ (sadece hosting/domain için göster) --}}
@if(in_array($tip, ['hosting', 'domain']) && $bitisTarihi)
    <div class="section" style="margin-top:16px">
        <div class="section-title">
            <i data-lucide="clock"></i>
            <span>Süre Bilgisi</span>
            @if($durum === 'aktif')
                <span class="badge badge-success" style="margin-left:auto">
                    <i data-lucide="check-circle" style="width:11px;height:11px"></i>
                    Aktif
                </span>
            @elseif($durum === 'sona-eriyor')
                <span class="badge badge-warning" style="margin-left:auto">
                    <i data-lucide="alert-triangle" style="width:11px;height:11px"></i>
                    Yakında Sona Eriyor
                </span>
            @elseif($durum === 'bitti')
                <span class="badge badge-danger" style="margin-left:auto">
                    <i data-lucide="x-circle" style="width:11px;height:11px"></i>
                    Süresi Doldu
                </span>
            @endif
        </div>

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
            {{-- Başlangıç --}}
            <div style="padding:14px;background:var(--bg-subtle);border:1px solid var(--border);border-radius:var(--radius-md);text-align:center">
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:600">
                    Başlangıç
                </div>
                <div style="font-size:18px;font-weight:700;margin-top:6px">
                    {{ $baslangicCarbon ? $baslangicCarbon->format('d.m.Y') : '—' }}
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                    Satış tarihi
                </div>
            </div>

            {{-- Bitiş --}}
            <div style="padding:14px;background:var(--brand-soft);border:1px solid rgba(184,182,46,0.3);border-radius:var(--radius-md);text-align:center">
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:600">
                    Bitiş
                </div>
                <div style="font-size:18px;font-weight:700;color:var(--brand-dark);margin-top:6px">
                    {{ $bitisFmt }}
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                    @if($bitisOtomatik)
                        <i data-lucide="info" style="width:11px;height:11px;display:inline;vertical-align:middle"></i>
                        Tahmini (+1 yıl)
                    @else
                        Yenileme tarihi
                    @endif
                </div>
            </div>

            {{-- Kalan Gün --}}
            <div style="padding:14px;border-radius:var(--radius-md);text-align:center;
                @if($durum === 'aktif') background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.3);
                @elseif($durum === 'sona-eriyor') background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3);
                @elseif($durum === 'bitti') background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.3);
                @endif">
                <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;font-weight:600">
                    {{ $durum === 'bitti' ? 'Doldu' : 'Kalan' }}
                </div>
                <div style="font-size:24px;font-weight:700;margin-top:6px;
                    @if($durum === 'aktif') color:var(--success);
                    @elseif($durum === 'sona-eriyor') color:var(--warning);
                    @elseif($durum === 'bitti') color:var(--danger);
                    @endif">
                    {{ abs($kalanGun) }} gün
                </div>
                <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                    @if($durum === 'bitti')
                        Önce sona erdi
                    @elseif($durum === 'sona-eriyor')
                        Yenileme gerekli!
                    @else
                        Aktif kalan süre
                    @endif
                </div>
            </div>
        </div>

        @if($bitisOtomatik)
            <small class="form-help" style="margin-top:12px;display:block;background:rgba(59,130,246,0.06);padding:10px 12px;border-radius:var(--radius-md);border-left:3px solid #3b82f6">
                <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                <strong>Bilgi:</strong>
                Bu bitiş tarihi veritabanında ayrı kayıtlı değildir, satış tarihine
                <strong>+1 yıl</strong>
                eklenerek tahmin edilmiştir. Gerçek bitiş tarihi farklı olabilir.
            </small>
        @endif
    </div>
@endif

<div class="form-grid" style="margin-top:20px;grid-template-columns:2fr 1fr">

    {{-- SOL: Satış detayları --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="file-text"></i>
                <span>Satış Bilgileri</span>
            </div>
            <div class="info-grid">
                <div class="info-item">
                    <span class="lbl">Satış ID</span>
                    <span class="val" style="font-family:monospace">#{{ $satis->id }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Tarih</span>
                    <span class="val">{{ $tFmt ?? '—' }}</span>
                </div>
                <div class="info-item">
                    <span class="lbl">Tutar</span>
                    <span class="val" style="color:var(--success);font-weight:700">
                        ₺{{ number_format($tutarVal, 2, ',', '.') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="lbl">Satış Tipi</span>
                    <span class="val">{{ $tipConfig['name'] }}</span>
                </div>

                @if(!empty($satis->paket_baslik))
                    <div class="info-item full">
                        <span class="lbl">Paket Adı</span>
                        <span class="val">{{ $satis->paket_baslik }}</span>
                    </div>
                @endif

                @if(!empty($satis->hosting_baslik))
                    <div class="info-item full">
                        <span class="lbl">Hosting Paketi</span>
                        <span class="val">{{ $satis->hosting_baslik }}</span>
                    </div>
                @endif

                @if(!empty($satis->domain))
                    <div class="info-item full">
                        <span class="lbl">Alan Adı</span>
                        <span class="val" style="font-family:monospace;color:var(--brand-dark)">
                            {{ $satis->domain }}
                        </span>
                    </div>
                @endif

                @if(!empty($satis->paket) && (int)$satis->paket > 0)
                    <div class="info-item">
                        <span class="lbl">Paket ID</span>
                        <span class="val">#{{ $satis->paket }}</span>
                    </div>
                @endif

                @if(!empty($satis->hosting) && (int)$satis->hosting > 0)
                    <div class="info-item">
                        <span class="lbl">Hosting ID</span>
                        <span class="val">#{{ $satis->hosting }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- EK BİLGİLER (varsa) --}}
        @php
            // satilanlar tablosunda olabilecek ek alanlar
            $ekAlanlar = [];
            $bilinmeyenler = ['id', 'uyeid', 'paket', 'paket_baslik', 'hosting', 'hosting_baslik',
                              'domain', 'tutar', 'tarih', 'created_at', 'updated_at',
                              'ad', 'soyad', 'email', 'musteri_ad', 'musteri_email', 'musteri_telefon', 'urun'];
            foreach (get_object_vars($satis) as $key => $value) {
                if (in_array($key, $bilinmeyenler)) continue;
                if (is_null($value) || $value === '' || $value === '0' || $value === 0) continue;
                $ekAlanlar[$key] = $value;
            }
        @endphp

        @if(count($ekAlanlar) > 0)
            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="list"></i>
                    <span>Ek Bilgiler</span>
                </div>
                <div class="info-grid">
                    @foreach($ekAlanlar as $key => $val)
                        <div class="info-item full">
                            <span class="lbl" style="font-family:monospace;font-size:11px">{{ $key }}</span>
                            <span class="val" style="font-size:13px">{{ is_scalar($val) ? $val : json_encode($val) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- SAĞ: Müşteri --}}
    <div>
        <div class="section">
            <div class="section-title">
                <i data-lucide="user"></i>
                <span>Müşteri Bilgileri</span>
            </div>

            @if(empty($satis->uyeid))
                <div class="alert alert-warning" style="margin:0;padding:12px;font-size:13px">
                    <i data-lucide="alert-triangle" style="width:14px;height:14px;display:inline;vertical-align:middle"></i>
                    Üye bilgisi bulunamadı (uyeid boş)
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
                    <div class="info-item">
                        <span class="lbl">Üye ID</span>
                        <span class="val">#{{ $satis->uyeid }}</span>
                    </div>
                </div>

                {{-- Üye linki (varsa) --}}
                @if(Route::has('admin.uyeler.duzenle'))
                    <a href="{{ route('admin.uyeler.duzenle', $satis->uyeid) }}"
                       class="btn btn-secondary btn-sm" style="width:100%;margin-top:12px">
                        <i data-lucide="external-link"></i>
                        <span>Üye Profilini Aç</span>
                    </a>
                @endif
            @endif
        </div>

        <div class="section" style="margin-top:14px">
            <div class="section-title">
                <i data-lucide="info"></i>
                <span>Bilgi</span>
            </div>
            <div style="font-size:12.5px;color:var(--text-secondary);line-height:1.6">
                <p style="margin:0 0 8px">
                    Bu satış {{ $tipConfig['name'] }} olarak kayıtlanmıştır.
                </p>
                @if($tHumanFmt)
                    <p style="margin:0">
                        <strong style="color:var(--text)">{{ $tHumanFmt }}</strong> tamamlandı.
                    </p>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    aside, .breadcrumb, .page-header .page-actions, .profile-actions, .btn { display: none !important; }
    .info-box-big, .section, .profile-header {
        box-shadow: none !important;
        page-break-inside: avoid;
    }
}
</style>

@endsection