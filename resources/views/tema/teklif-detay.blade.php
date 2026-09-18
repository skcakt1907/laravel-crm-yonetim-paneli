@extends('layouts.master')

@section('title', ($teklif->baslik ?? 'Size Özel Teklif') . ' — İş Ortağım')
@section('description', 'Size özel hazırlanmış paket teklifi. Detayları inceleyin, dilediğiniz paketi sepete ekleyin veya satın alın.')

@section('page_override')
<style>
    .tk-hero {
        background: linear-gradient(135deg, #fbfbf2 0%, #f4f4e0 100%);
        border-bottom: 1px solid #e8e8c8;
        padding: 96px 0 30px;
    }
    .tk-breadcrumb {
        display: flex; align-items: center; gap: 6px;
        font-size: 13px; color: #6b7280; margin-bottom: 14px; flex-wrap: wrap;
    }
    .tk-breadcrumb a { color: #6b7280; text-decoration: none; }
    .tk-breadcrumb a:hover { color: #8a8a1f; }
    .tk-badge {
        display: inline-block; padding: 5px 14px; border-radius: 20px;
        font-size: 12px; font-weight: 700; letter-spacing: .04em; margin-bottom: 12px;
    }
    .tk-hero-title { font-size: 30px; font-weight: 800; color: #1a2332; margin: 0; line-height: 1.2; }
    .tk-hero-sub { font-size: 14px; color: #6b7280; margin-top: 8px; }

    .tk-main { padding: 36px 0 60px; background: #f6f7f1; }

    /* Kart genel */
    .tk-card {
        background: #fff; border-radius: 16px; border: 1px solid #e9ead4;
        box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 24px; margin-bottom: 18px;
    }
    .tk-card-title {
        font-size: 14px; font-weight: 700; color: #374151; margin: 0 0 14px;
        display: flex; align-items: center; gap: 8px;
        text-transform: uppercase; letter-spacing: .04em;
    }
    .tk-card-body { font-size: 14px; color: #4b5563; line-height: 1.8; white-space: pre-line; }

    /* Galeri */
    .tk-gallery-main {
        padding: 14px; display: flex; align-items: center; justify-content: center;
        background: #f9fafb; min-height: 240px; border-radius: 12px; border: 1px solid #eef0e6;
    }
    .tk-gallery-main img { max-width: 100%; max-height: 420px; object-fit: contain; border-radius: 8px; display: block; }
    .tk-detay-main-img { max-width: 100%; max-height: 420px; object-fit: contain; border-radius: 8px; display: block; }
    .tk-gallery-thumbs { display: flex; flex-wrap: wrap; gap: 8px; padding: 12px 0 0; }
    .tk-thumb {
        border: 2px solid transparent; border-radius: 8px; overflow: hidden;
        cursor: pointer; background: none; padding: 0; transition: border-color .15s, box-shadow .15s;
    }
    .tk-thumb img { width: 62px; height: 48px; object-fit: cover; display: block; }
    .tk-thumb:hover, .tk-thumb.is-active { border-color: #b8b62e; box-shadow: 0 0 0 2px #ecedca; }

    /* Özellikler */
    .tk-ozellik-list { display: grid; grid-template-columns: 1fr; gap: 12px; }
    .tk-ozellik-item { display: flex; align-items: flex-start; gap: 10px; font-size: 14px; color: #374151; line-height: 1.5; }
    .tk-ozellik-item .mdi { color: #8a8a1f; margin-top: 2px; font-size: 18px; }

    /* Paket kartı (collapse) */
    .tk-pkg {
        background: #fff; border-radius: 14px; border: 1px solid #e9ead4;
        box-shadow: 0 2px 10px rgba(0,0,0,.04); overflow: hidden; margin-bottom: 14px;
    }
    .tk-pkg-head {
        display: flex; align-items: center; justify-content: space-between;
        gap: 12px; padding: 14px 18px; cursor: pointer; transition: background .15s;
    }
    .tk-pkg-head:hover { background: #fafaf0; }
    .tk-pkg-head .name { font-size: 15px; font-weight: 700; color: #1a2332; }
    .tk-pkg-head .right { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
    .tk-pkg-head .price { font-size: 16px; font-weight: 800; color: #8a8a1f; white-space: nowrap; }
    .tk-pkg-head .chev { transition: transform .2s; color: #9aa0a6; font-size: 20px; line-height: 1; }
    .tk-pkg-head.is-open .chev { transform: rotate(180deg); }
    .tk-pkg-body { display: none; padding: 0 18px 18px; }
    .tk-pkg-body.is-open { display: block; }
    .tk-pkg-short { font-size: 14px; color: #4b5563; line-height: 1.7; margin: 12px 0 0; }
    .tk-pkg-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }

    /* SAĞ bilgi kutusu (sticky) */
    .tk-side {
        background: #fff; border-radius: 16px; border: 1px solid #e9ead4;
        box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 24px; position: sticky; top: 96px;
    }
    .tk-side-total-lbl { font-size: 12px; color: #9aa0a6; letter-spacing: .04em; }
    .tk-side-total { font-size: 32px; font-weight: 800; color: #8a8a1f; margin: 2px 0 16px; }
    .tk-side-rows { border-top: 1px solid #f0f1dc; padding-top: 14px; margin-bottom: 18px; }
    .tk-side-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 0; font-size: 13.5px; border-bottom: 1px dashed #f0f1dc;
    }
    .tk-side-row:last-child { border-bottom: none; }
    .tk-side-row .k { color: #9aa0a6; }
    .tk-side-row .v { color: #1a2332; font-weight: 600; text-align: right; }

    .tk-btn {
        width: 100%; display: inline-flex; align-items: center; justify-content: center;
        gap: 8px; padding: 13px 18px; border-radius: 10px; font-size: 14px; font-weight: 700;
        text-decoration: none; border: none; cursor: pointer; transition: transform .1s, opacity .15s;
        margin-bottom: 10px;
    }
    .tk-btn:last-child { margin-bottom: 0; }
    .tk-btn:hover { transform: translateY(-1px); }
    .tk-btn-primary { background: #1a2332; color: #fff; }
    .tk-btn-secondary { background: #b8b62e; color: #1a2332; }
    .tk-btn-login { background: #8a8a1f; color: #fff; }

    .tk-file-link {
        display: inline-flex; align-items: center; gap: 8px; background: #1a2332; color: #fff;
        padding: 11px 20px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px;
    }
    .tk-foot { text-align: center; font-size: 12px; color: #aaadb4; margin-top: 18px; }

    @media (max-width: 991px) {
        .tk-side { position: static; margin-top: 4px; }
    }
    @media (max-width: 768px) {
        .tk-hero-title { font-size: 23px; }
        .tk-side-total { font-size: 26px; }
    }
</style>
@endsection

@section('content')
@php
    use Illuminate\Support\Facades\Route;

    $pb = $teklif->para_birimi ?? 'TL';
    $sembol = match(strtoupper($pb)) { 'USD'=>'$', 'EUR'=>'€', 'AED'=>'د.إ', default=>'₺' };

    $durum = $teklif->durum ?? 'beklemede';
    $durumMap = [
        'beklemede'  => ['Beklemede',  '#8a6d00', 'rgba(184,182,46,.18)'],
        'gonderildi' => ['Gönderildi', '#1d4ed8', 'rgba(59,130,246,.14)'],
        'onaylandi'  => ['Onaylandı',  '#047857', 'rgba(16,185,129,.14)'],
        'odendi'     => ['Ödendi',     '#047857', 'rgba(16,185,129,.14)'],
        'paid'       => ['Ödendi',     '#047857', 'rgba(16,185,129,.14)'],
        'reddedildi' => ['Reddedildi', '#b91c1c', 'rgba(239,68,68,.14)'],
        'iptal'      => ['İptal',      '#b91c1c', 'rgba(239,68,68,.14)'],
    ];
    $dr = $durumMap[$durum] ?? ['Beklemede', '#8a6d00', 'rgba(184,182,46,.18)'];

    $musteriAd = trim(($teklif->ad ?? '') . ' ' . ($teklif->soyad ?? ''));
    $noimage = asset('tema/uploads/noimage.png');

    $galeriArr = $galeri ?? [];

    // Teklifin kendi görselleri (kapak + yüklenen galeri)
    $tkImgs = [];
    foreach (($teklifGorselleri ?? []) as $r) {
        if (!empty($r)) { $tkImgs[] = asset('tema/uploads/webpaketleri/' . $r); }
    }
    $tkImgs = array_values(array_unique($tkImgs));

    // Paket görselleri (kapak + webpaketresim)
    $paketGorselleri = function ($p) use ($galeriArr, $noimage) {
        $imgs = [];
        if (!empty($p->paket_resim)) {
            $imgs[] = function_exists('get_package_image_path')
                ? get_package_image_path($p->paket_resim)
                : asset('tema/uploads/webpaketleri/' . $p->paket_resim);
        }
        $rid = $p->paket_id ?? null;
        if ($rid && isset($galeriArr[$rid])) {
            foreach ($galeriArr[$rid] as $g) {
                $gr = is_array($g) ? ($g['resim'] ?? null) : ($g->resim ?? null);
                if ($gr) { $imgs[] = asset('tema/uploads/webpaketleri/' . $gr); }
            }
        }
        $imgs = array_values(array_unique($imgs));
        if (empty($imgs)) { $imgs[] = $noimage; }
        return $imgs;
    };

    $girisYaptiMi = auth()->guard('uye')->check();
    $paketVar = ($paketler ?? collect())->isNotEmpty();
    $ilkPaket = $paketVar ? $paketler->first() : null;

    // Tarih
    $tarihFmt = null;
    if (!empty($teklif->created_at)) {
        try { $tarihFmt = \Carbon\Carbon::parse($teklif->created_at)->format('d.m.Y'); } catch (\Throwable $e) {}
    }
@endphp

{{-- HERO --}}
<div class="tk-hero">
    <div class="container">
        <div class="tk-breadcrumb">
            <a href="{{ localized_route('anasayfa') }}">{{ __('messages.home') }}</a>
            <span>›</span>
            <a href="{{ localized_route('paketler') }}">{{ __('messages.packages') }}</a>
            <span>›</span>
            <span style="color:#1a2332">{{ __('messages.special_offer_for_you') }}</span>
        </div>
        <span class="tk-badge" style="background:{{ $dr[2] }};color:{{ $dr[1] }}">{{ $dr[0] }}</span>
        <h1 class="tk-hero-title">{{ $teklif->baslik ?? 'Size Özel Teklif' }}</h1>
        <div class="tk-hero-sub">{{ __('messages.special_offer_sub') }}</div>
    </div>
</div>

{{-- MAIN --}}
<div class="tk-main">
    <div class="container">
        <div class="row">

            {{-- SOL KOLON --}}
            <div class="col-lg-8">

                {{-- GALERİ --}}
                @if(count($tkImgs) > 0)
                    <div class="tk-card" style="padding:16px">
                        <div class="tk-gallery-main">
                            <img class="tk-detay-main-img" src="{{ $tkImgs[0] }}" alt="{{ $teklif->baslik ?? '' }}"
                                 onerror="this.onerror=null;this.src='{{ $noimage }}';">
                        </div>
                        @if(count($tkImgs) > 1)
                            <div class="tk-gallery-thumbs" id="tkDetayThumbs">
                                @foreach($tkImgs as $i => $src)
                                    <button type="button" class="tk-thumb {{ $i === 0 ? 'is-active' : '' }}" data-src="{{ $src }}">
                                        <img src="{{ $src }}" alt="" onerror="this.onerror=null;this.src='{{ $noimage }}';">
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                {{-- ÖZELLİKLER --}}
                @if(!empty($teklif->ozellik))
                    <div class="tk-card">
                        <div class="tk-card-title"><i class="mdi mdi-check-circle-outline"></i> {{ __('messages.main_features') }}</div>
                        <div class="tk-ozellik-list">
                            @foreach(preg_split('/\r\n|\r|\n/', $teklif->ozellik) as $satir)
                                @php $satir = trim($satir); @endphp
                                @if($satir !== '')
                                    <div class="tk-ozellik-item"><i class="mdi mdi-check"></i><span>{{ $satir }}</span></div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- AÇIKLAMA --}}
                @if(!empty($teklif->aciklama_html))
                    <div class="tk-card">
                        <div class="tk-card-title"><i class="mdi mdi-file-document-outline"></i> {{ __('messages.description') }}</div>
                        <div class="tk-card-body" style="white-space:normal">{!! $teklif->aciklama_html !!}</div>
                    </div>
                @elseif(!empty($teklif->aciklama))
                    @php
                        $aciklamaHam = $teklif->aciklama;
                        // HTML etiketi içeriyor mu? (TinyMCE / kopyalanan zengin metin)
                        $htmlMi = $aciklamaHam !== strip_tags($aciklamaHam);
                        if ($htmlMi) {
                            // Yalnız güvenli etiketlere izin ver (XSS koruması)
                            $izinli = '<p><br><b><strong><i><em><u><s><ul><ol><li><h1><h2><h3><h4><h5><h6><span><a><blockquote><table><thead><tbody><tr><td><th><div>';
                            $temiz = strip_tags($aciklamaHam, $izinli);
                            // data-* ve style attribute'larını sök (kopyalanan içerikten gelen çöp)
                            $temiz = preg_replace('/\s*(data-[\w-]+|style|class|id)="[^"]*"/i', '', $temiz);
                            $temiz = preg_replace("/\s*(data-[\w-]+|style|class|id)='[^']*'/i", '', $temiz);
                        }
                    @endphp
                    <div class="tk-card">
                        <div class="tk-card-title"><i class="mdi mdi-file-document-outline"></i> {{ __('messages.description') }}</div>
                        @if($htmlMi)
                            <div class="tk-card-body" style="white-space:normal">{!! $temiz !!}</div>
                        @else
                            <div class="tk-card-body" style="white-space:pre-wrap">{{ $aciklamaHam }}</div>
                        @endif
                    </div>
                @endif

                {{-- PAKETLER (collapse) --}}
                @if($paketVar)
                    <div class="tk-card">
                        <div class="tk-card-title"><i class="mdi mdi-package-variant-closed"></i> {{ __('messages.offer_scope') }}</div>
                        @foreach($paketler as $idx => $p)
                            @php
                                $imgs = $paketGorselleri($p);
                                $satir = (float)($p->satir_toplam_tl ?? $p->birim_fiyat_tl ?? 0);
                                $acik = $idx === 0;
                            @endphp
                            <div class="tk-pkg">
                                <div class="tk-pkg-head {{ $acik ? 'is-open' : '' }}" data-tk-toggle>
                                    <div class="name">{{ $p->adi ?? '—' }}</div>
                                    <div class="right">
                                        <span class="price">{{ $sembol }}{{ number_format($satir, 2, ',', '.') }}</span>
                                        <span class="chev">⌄</span>
                                    </div>
                                </div>
                                <div class="tk-pkg-body {{ $acik ? 'is-open' : '' }}">
                                    <div class="tk-gallery-main" style="min-height:160px">
                                        <img class="tk-main-img" src="{{ $imgs[0] }}" alt="{{ $p->adi ?? '' }}"
                                             onerror="this.onerror=null;this.src='{{ $noimage }}';">
                                    </div>
                                    @if(count($imgs) > 1)
                                        <div class="tk-gallery-thumbs">
                                            @foreach($imgs as $i => $src)
                                                <button type="button" class="tk-thumb {{ $i === 0 ? 'is-active' : '' }}" data-src="{{ $src }}">
                                                    <img src="{{ $src }}" alt="" onerror="this.onerror=null;this.src='{{ $noimage }}';">
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if(!empty($p->paket_kisa))
                                        <div class="tk-pkg-short">{!! strip_tags($p->paket_kisa) !!}</div>
                                    @endif
                                    <div class="tk-pkg-actions">
                                        @if($girisYaptiMi)
                                            <form action="{{ localized_route('web.paket.satinal', $p->paket_id) }}" method="GET" style="flex:1;min-width:150px;margin:0">
                                                <input type="hidden" name="sure" value="1">
                                                <button type="submit" class="tk-btn tk-btn-secondary" style="margin:0">
                                                    <i class="mdi mdi-basket-outline"></i> {{ __('messages.add_to_cart') }}
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ localized_route('giris') }}" class="tk-btn tk-btn-login" style="margin:0">
                                                <i class="mdi mdi-login"></i> {{ __('messages.login_to_purchase') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- DOSYA --}}
                @if(!empty($teklif->dosya))
                    <div class="tk-card">
                        <div class="tk-card-title"><i class="mdi mdi-paperclip"></i> {{ __('messages.offer_file') }}</div>
                        <a href="{{ asset('storage/' . $teklif->dosya) }}" target="_blank" class="tk-file-link">
                            <i class="mdi mdi-download"></i> {{ __('messages.download_offer_file') }}
                        </a>
                    </div>
                @endif

            </div>

            {{-- SAĞ KOLON: bilgi kutusu --}}
            <div class="col-lg-4">
                <div class="tk-side">
                    @if($musteriAd)
                        <div style="font-size:12px;color:#9aa0a6">{{ __('messages.prepared_for_customer') }}</div>
                        <div style="font-size:15px;font-weight:700;color:#1a2332;margin-bottom:12px">{{ $musteriAd }}</div>
                    @endif

                    <div class="tk-side-total-lbl">{{ __('messages.total_amount') }}</div>
                    <div class="tk-side-total">{{ $sembol }}{{ number_format((float)($teklif->toplam_tl ?? 0), 2, ',', '.') }}</div>

                    <div class="tk-side-rows">
                        <div class="tk-side-row">
                            <span class="k">{{ __('messages.table_status') }}</span>
                            <span class="v" style="color:{{ $dr[1] }}">{{ $dr[0] }}</span>
                        </div>
                        @if($tarihFmt)
                            <div class="tk-side-row">
                                <span class="k">{{ __('messages.table_date') }}</span>
                                <span class="v">{{ $tarihFmt }}</span>
                            </div>
                        @endif
                        @if(!empty($kategoriAdi))
                            <div class="tk-side-row">
                                <span class="k">Kategori</span>
                                <span class="v">{{ $kategoriAdi }}</span>
                            </div>
                        @endif
                        <div class="tk-side-row">
                            <span class="k">Para Birimi</span>
                            <span class="v">{{ $pb }}</span>
                        </div>
                        <div class="tk-side-row">
                            <span class="k">{{ __('messages.offer_no') }}</span>
                            <span class="v">#{{ $teklif->id }}</span>
                        </div>
                    </div>

                    {{-- BUTON: paket varsa Satın Al, yoksa giriş/iletişim --}}
                    @if($girisYaptiMi)
                        @if($paketVar && $ilkPaket)
                            <form action="{{ localized_route('web.paket.satinal', $ilkPaket->paket_id) }}" method="GET" style="margin:0">
                                <input type="hidden" name="sure" value="1">
                                <button type="submit" class="tk-btn tk-btn-primary">
                                    <i class="mdi mdi-cart"></i> {{ __('messages.buy_now') }}
                                </button>
                            </form>
                            <form action="{{ localized_route('web.paket.satinal', $ilkPaket->paket_id) }}" method="GET" style="margin:0">
                                <input type="hidden" name="sure" value="1">
                                <button type="submit" class="tk-btn tk-btn-secondary">
                                    <i class="mdi mdi-basket-outline"></i> {{ __('messages.add_to_cart') }}
                                </button>
                            </form>
                        @else
                            {{-- Paketsiz (manuel) teklif: iletişim --}}
                            <a href="{{ localized_route('iletisim') }}" class="tk-btn tk-btn-primary">
                                <i class="mdi mdi-message-text-outline"></i> {{ __('messages.contact_now') }}
                            </a>
                        @endif
                    @else
                        <a href="{{ localized_route('giris') }}" class="tk-btn tk-btn-login">
                            <i class="mdi mdi-login"></i> {{ __('messages.login_to_purchase') }}
                        </a>
                    @endif
                </div>
            </div>

        </div>

        <p class="tk-foot">
            Teklif No: #{{ $teklif->id }} ·
            Oluşturma: {{ !empty($teklif->created_at) ? \Carbon\Carbon::parse($teklif->created_at)->format('d.m.Y H:i') : '—' }}
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Üstteki teklif galerisi
    (function () {
        var dMain = document.querySelector('.tk-detay-main-img');
        var dThumbs = document.getElementById('tkDetayThumbs');
        if (dMain && dThumbs) {
            dThumbs.querySelectorAll('.tk-thumb').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    dMain.src = btn.getAttribute('data-src');
                    dThumbs.querySelectorAll('.tk-thumb').forEach(function (b) { b.classList.remove('is-active'); });
                    btn.classList.add('is-active');
                });
            });
        }
    })();

    // Paket kartları: collapse + kendi galerisi
    document.querySelectorAll('[data-tk-toggle]').forEach(function (head) {
        head.addEventListener('click', function () {
            var body = head.nextElementSibling;
            head.classList.toggle('is-open');
            if (body) body.classList.toggle('is-open');
        });
    });
    document.querySelectorAll('.tk-pkg-body').forEach(function (body) {
        var mainImg = body.querySelector('.tk-main-img');
        body.querySelectorAll('.tk-thumb').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (mainImg) mainImg.src = btn.getAttribute('data-src');
                body.querySelectorAll('.tk-thumb').forEach(function (b) { b.classList.remove('is-active'); });
                btn.classList.add('is-active');
            });
        });
    });
});
</script>

@endsection