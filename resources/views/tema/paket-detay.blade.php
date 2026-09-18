@extends('layouts.master')

@section('page_override')
<style>
    .pd-hero {
        background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%);
        border-bottom: 1px solid #e0e7ff;
        padding: 96px 0 28px;
    }
    .pd-breadcrumb {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 13px;
        color: #6b7280;
        flex-wrap: wrap;
    }
    .pd-breadcrumb a {
        color: #4f46e5;
        text-decoration: none;
        font-weight: 500;
    }
    .pd-breadcrumb a:hover { text-decoration: underline; }
    .pd-breadcrumb .sep { color: #d1d5db; }
    .pd-hero-title {
        font-size: 32px;
        font-weight: 800;
        color: #111827;
        margin: 10px 0 6px;
        line-height: 1.25;
    }
    .pd-hero-sub {
        font-size: 15px;
        color: #6b7280;
        margin: 0;
    }
    .pd-main {
        background: #f9fafb;
        padding: 40px 0 80px;
    }
    /* Gallery */
    .pd-gallery-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .pd-gallery-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #9ca3af;
        padding: 14px 16px 0;
    }
    .pd-gallery-main {
        padding: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f9fafb;
        min-height: 260px;
    }
    .pd-gallery-main img {
        max-width: 100%;
        max-height: 380px;
        object-fit: contain;
        border-radius: 10px;
        display: block;
    }
    .pd-gallery-thumbs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 10px 12px 14px;
        border-top: 1px solid #f3f4f6;
    }
    .pd-gallery-thumb {
        border: 2px solid transparent;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        background: none;
        padding: 0;
        transition: border-color .15s, box-shadow .15s;
    }
    .pd-gallery-thumb img {
        width: 64px;
        height: 50px;
        object-fit: cover;
        display: block;
    }
    .pd-gallery-thumb:hover,
    .pd-gallery-thumb.is-active {
        border-color: #4f46e5;
        box-shadow: 0 0 0 2px #c7d2fe;
    }
    /* Content card */
    .pd-content-card {
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        padding: 24px;
        margin-bottom: 20px;
    }
    .pd-section-title {
        font-size: 14px;
        font-weight: 700;
        color: #374151;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .pd-section-title i { color: #4f46e5; }
    .pd-content-body {
        font-size: 14px;
        color: #4b5563;
        line-height: 1.85;
    }
    .pd-content-body ul, .pd-content-body ol { padding-left: 20px; }
    .pd-content-body li { margin-bottom: 4px; }
    /* Price card */
    .pd-price-card {
        background: #fff;
        border-radius: 20px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 24px rgba(0,0,0,.09);
        padding: 28px 24px;
        position: sticky;
        top: 80px;
    }
    .pd-pkg-label {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: 6px;
    }
    .pd-pkg-title {
        font-size: 22px;
        font-weight: 800;
        color: #111827;
        margin-bottom: 8px;
        line-height: 1.3;
    }
    .pd-pkg-short {
        font-size: 13.5px;
        color: #6b7280;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    .pd-price-box {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        border-radius: 14px;
        padding: 18px 20px;
        margin-bottom: 18px;
    }
    .pd-price-badge {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .14em;
        background: rgba(255,255,255,.18);
        color: #fff;
        padding: 3px 10px;
        border-radius: 999px;
        display: inline-block;
        margin-bottom: 8px;
    }
    .pd-price-amount {
        font-size: 36px;
        font-weight: 800;
        color: #fff;
        line-height: 1;
    }
    .pd-btn-primary {
        display: block;
        width: 100%;
        padding: 13px;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 700;
        text-align: center;
        cursor: pointer;
        border: none;
        background: #d4d25b;
        color: #1a1a00;
        margin-bottom: 10px;
        transition: background .15s, transform .1s;
        text-decoration: none;
    }
    .pd-btn-primary:hover {
        background: #c4c24b;
        color: #1a1a00;
        transform: translateY(-1px);
        text-decoration: none;
    }
    .pd-btn-secondary {
        display: block;
        width: 100%;
        padding: 11px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        cursor: pointer;
        border: 1.5px solid #e5e7eb;
        background: #f9fafb;
        color: #374151;
        margin-bottom: 18px;
        transition: background .15s;
        text-decoration: none;
    }
    .pd-btn-secondary:hover {
        background: #f3f4f6;
        color: #374151;
        text-decoration: none;
    }
    .pd-meta-row {
        display: flex;
        justify-content: space-between;
        font-size: 12.5px;
        color: #9ca3af;
        margin-bottom: 16px;
    }
    .pd-cats {
        border-top: 1px solid #f3f4f6;
        padding-top: 14px;
        margin-top: 4px;
    }
    .pd-cats-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .1em;
        color: #9ca3af;
        margin-bottom: 8px;
    }
    .pd-cat-tag {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 999px;
        background: #eef2ff;
        color: #4f46e5;
        font-size: 12px;
        font-weight: 600;
        margin: 2px 4px 2px 0;
        text-decoration: none;
        border: 1px solid #c7d2fe;
        transition: background .15s;
    }
    .pd-cat-tag:hover {
        background: #e0e7ff;
        color: #3730a3;
        text-decoration: none;
    }
    @media (max-width: 991px) {
        .pd-price-card { position: static; margin-top: 24px; }
        .pd-hero-title { font-size: 24px; }
    }
    @media (max-width: 576px) {
        .pd-gallery-main { min-height: 180px; }
        .pd-gallery-main img { max-height: 240px; }
    }
</style>
@endsection

@section('content')


@php
    $locale = app()->getLocale();
    if ($locale === 'en') {
        $paketBaslik = $paket->adi_en ?: $paket->adi;
        $paketKisa   = $paket->kisa_en ?: $paket->kisa;
    } elseif ($locale === 'ar') {
        $paketBaslik = $paket->adi_ar ?: $paket->adi;
        $paketKisa   = $paket->kisa_ar ?: $paket->kisa;
    } else {
        $paketBaslik = $paket->adi;
        $paketKisa   = $paket->kisa;
    }
    $currency    = request('currency') ?? session('currency', 'TRY');
    $fiyat       = (float) \App\Helpers\DovizKuruHelper::tldenCevir($paket->tutar, $currency);
    $temel_fiyat = (float) $paket->tutar;
    $sembol      = match($currency) { 'USD'=>'$','EUR'=>'€','AED'=>'د.إ',default=>'₺' };

    // Ödeme tipi: 'aylik' paketlerde ay seçici + "Aylık Ödeme" rozeti
    $odemeTipi = $paket->odeme_tipi ?? 'tek';
@endphp

<!-- HERO / BREADCRUMBs -->
<div class="pd-hero">
    <div class="container">
        <nav class="pd-breadcrumb">
            <a href="{{ localized_route('anasayfa') }}">{{ __('messages.home') }}</a>
            <span class="sep">/</span>
            <a href="{{ localized_route('paketler') }}">{{ __('messages.packages') }}</a>
            <span class="sep">/</span>
            <span style="color:#111827; font-weight:600;">{{ $paketBaslik }}</span>
        </nav>
        <h1 class="pd-hero-title">{{ $paketBaslik }}</h1>
        @if($paketKisa)
            <p class="pd-hero-sub">{!! strip_tags($paketKisa) !!}</p>
        @endif
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="pd-main">
    <div class="container">
        <div class="row">
            <!-- Sol: Galeri + İçerik -->
            <div class="col-lg-7">

                @php
                    $slides = collect([]);
                    if (!empty($paket->resim)) {
                        $coverUrl  = get_package_image_path($paket->resim);
                        $coverPath = public_path(ltrim(parse_url($coverUrl, PHP_URL_PATH), '/'));
                        if (file_exists($coverPath)) { $slides->push(['src' => $coverUrl]); }
                    }
                    if ($resimler && $resimler->count() > 0) {
                        foreach ($resimler as $r) {
                            $gUrl  = asset('tema/uploads/webpaketleri/'.$r->resim);
                            $gPath = public_path('tema/uploads/webpaketleri/'.$r->resim);
                            if (file_exists($gPath)) { $slides->push(['src' => $gUrl]); }
                        }
                    }
                    $slides = $slides->unique('src')->values();
                    if ($slides->isEmpty()) { $slides->push(['src' => asset('tema/uploads/noimage.png')]); }
                @endphp

                <!-- Gallery -->
                <div class="pd-gallery-card">
                    <div class="pd-gallery-label">Galeri</div>
                    <div class="pd-gallery-main">
                        <img id="pd-main-img"
                             src="{{ $slides[0]['src'] }}"
                             alt="{{ $paketBaslik }}"
                             onerror="this.onerror=null;this.src='{{ asset('tema/uploads/noimage.png') }}';">
                    </div>
                    @if($slides->count() > 1)
                        <div class="pd-gallery-thumbs">
                            @foreach($slides as $i => $slide)
                                <button type="button"
                                        class="pd-gallery-thumb {{ $i === 0 ? 'is-active' : '' }}"
                                        data-src="{{ $slide['src'] }}">
                                    <img src="{{ $slide['src'] }}"
                                         alt="{{ $paketBaslik }}"
                                         onerror="this.onerror=null;this.src='{{ asset('tema/uploads/noimage.png') }}';">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                @php
                    if ($locale === 'en')      { $paketAciklama = $paket->aciklama_en ?: $paket->aciklama; }
                    elseif ($locale === 'ar')  { $paketAciklama = $paket->aciklama_ar ?: $paket->aciklama; }
                    else                        { $paketAciklama = $paket->aciklama; }
                @endphp
                @if($paketAciklama)
                    <div class="pd-content-card">
                        <div class="pd-section-title">
                            <i class="mdi mdi-file-document-outline"></i>{{ __('messages.description') }}
                        </div>
                        <div class="pd-content-body">{!! $paketAciklama !!}</div>
                    </div>
                @endif

                @php
                    if ($locale === 'en')      { $paketTalimat = $paket->talimat_en ?: $paket->talimat; }
                    elseif ($locale === 'ar')  { $paketTalimat = $paket->talimat_ar ?: $paket->talimat; }
                    else                        { $paketTalimat = $paket->talimat; }
                @endphp
                @if($paketTalimat)
                    <div class="pd-content-card">
                        <div class="pd-section-title">
                            <i class="mdi mdi-cog-outline"></i>{{ __('messages.installation_instructions') }}
                        </div>
                        <div class="pd-content-body">{!! $paketTalimat !!}</div>
                    </div>
                @endif

                @php
                    if ($locale === 'en')      { $paketOzellik = $paket->ozellik_en ?: $paket->ozellik; }
                    elseif ($locale === 'ar')  { $paketOzellik = $paket->ozellik_ar ?: $paket->ozellik; }
                    else                        { $paketOzellik = $paket->ozellik; }
                @endphp
                @if($paketOzellik)
                    <div class="pd-content-card">
                        <div class="pd-section-title">
                            <i class="mdi mdi-check-circle-outline"></i>{{ __('messages.features') }}
                        </div>
                        <div class="pd-content-body">
                            {!! str_replace('<i class="fa fa-check"></i>', '<i class="mdi mdi-check" style="color:#4f46e5;"></i>', nl2br($paketOzellik)) !!}
                        </div>
                    </div>
                @endif

            </div>

            <!-- Sağ: Fiyat Kartı -->
            <div class="col-lg-5">
                <div class="pd-price-card">
                    <div class="pd-pkg-label">{{ __('messages.packages') }}</div>
                    <div class="pd-pkg-title">{{ $paketBaslik }}</div>
                    @if($paketKisa)
                        <div class="pd-pkg-short">{!! strip_tags($paketKisa) !!}</div>
                    @endif

                    <div class="pd-price-box">
                        @if($odemeTipi === 'aylik')
                            <div class="pd-price-badge" style="background:#ecfdf5;color:#059669;">{{ __('messages.monthly_payment') }}</div>
                            <div class="pd-price-amount" id="fiyat_goster_ana">
                                {{ $sembol }}{{ number_format($fiyat, 2, ',', '.') }}
                                <span style="font-size:15px;color:#6b7280;font-weight:600;">/ay</span>
                            </div>
                        @else
                            <div class="pd-price-badge">{{ __('messages.one_time_payment') }}</div>
                            <div class="pd-price-amount" id="fiyat_goster_ana">
                                {{ $sembol }}{{ number_format($fiyat, 2, ',', '.') }}
                            </div>
                        @endif
                    </div>

                    @if($odemeTipi === 'aylik')
                        {{-- Ay seçici (1-3-6-9-12) --}}
                        <div class="pd-sure-secici" style="margin:16px 0;">
                            <div style="font-size:13px;font-weight:600;color:#374151;margin-bottom:8px;">{{ __('messages.select_duration') }}</div>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                @foreach([1=>0, 3=>10, 6=>15, 9=>18, 12=>20] as $ay => $ind)
                                    <button type="button"
                                            class="pd-ay-btn{{ $ay === 1 ? ' is-active' : '' }}"
                                            data-ay="{{ $ay }}" data-indirim="{{ $ind }}"
                                            style="flex:1;min-width:58px;padding:10px 6px;border:2px solid {{ $ay === 1 ? '#4f46e5' : '#e5e7eb' }};border-radius:10px;background:{{ $ay === 1 ? '#eef2ff' : '#fff' }};cursor:pointer;font-weight:700;color:#111827;transition:.15s;position:relative;">
                                        {{ $ay }} Ay
                                        @if($ind > 0)<span style="display:block;font-size:10px;color:#059669;font-weight:600;margin-top:2px;">%{{ $ind }} indirim</span>@endif
                                    </button>
                                @endforeach
                            </div>
                            <div id="pd-toplam-satiri" style="margin-top:12px;font-size:13px;color:#6b7280;display:none;">
                                {{-- Canlı <span> içerdiği için metin parça parça çevriliyor --}}
                                {{ __('messages.total') }} (<span id="pd-secili-ay">1</span> {{ mb_strtolower(__('messages.month')) }}):
                                <strong id="pd-toplam-tutar" style="color:#111827;font-size:16px;"></strong>
                                <span id="pd-indirim-etiket" style="color:#059669;font-weight:600;"></span>
                            </div>
                        </div>
                    @endif

                    <div class="pd-meta-row">
                        <span><i class="mdi mdi-eye"></i> {{ $izlenme }} {{ __('messages.views') }}</span>
                        @if($kategoriler && $kategoriler->count() > 0)
                            <span><i class="mdi mdi-tag-outline"></i> {{ \App\Helpers\TranslationHelper::translate($kategoriler->first()->adi) }}</span>
                        @endif
                    </div>

                    @if(!empty($paket->demo_link))
                        <a href="{{ $paket->demo_link }}" target="_blank" rel="noopener" class="pd-btn-secondary" style="background:#0ea5e9;color:#fff;border-color:#0ea5e9;">
                            <i class="mdi mdi-eye"></i> {{ __('messages.view_live_demo') }}
                        </a>
                    @endif
                    @if(!empty($paket->demo_admin_link))
                        <a href="{{ $paket->demo_admin_link }}" target="_blank" rel="noopener" class="pd-btn-secondary" style="background:#6366f1;color:#fff;border-color:#6366f1;">
                            <i class="mdi mdi-shield-account"></i> {{ __('messages.view_demo_admin') }}
                        </a>
                    @endif

                    @auth('uye')
                        <form action="{{ localized_route('web.paket.satinal', $paket->id) }}" method="GET" style="margin:0;">
                            <input type="hidden" name="sure" value="1" class="pd-sure-input">
                            <button type="submit" class="pd-btn-primary">
                                <i class="mdi mdi-cart"></i> {{ __('messages.buy_now') }}
                            </button>
                        </form>
                        <form action="{{ localized_route('web.paket.satinal', $paket->id) }}" method="GET" style="margin:0;">
                            <input type="hidden" name="sure" value="1" class="pd-sure-input">
                            <button type="submit" class="pd-btn-secondary">
                                <i class="mdi mdi-basket-outline"></i> {{ __('messages.add_to_cart') }}
                            </button>
                        </form>
                    @else
                        <a href="{{ localized_route('giris') }}" class="pd-btn-primary">
                            <i class="mdi mdi-login"></i> {{ __('messages.login_to_buy') }}
                        </a>
                    @endauth

                    {{-- Yanında Satın Alınabilecekler (madde 15) --}}
                    @if(isset($ekHizmetler) && $ekHizmetler->count() > 0)
                        <div class="pd-addons" style="margin-top:18px;padding-top:18px;border-top:1px solid #eee;">
                            <div style="font-size:14px;font-weight:800;color:#1a1a1a;margin-bottom:10px;">🧩 {{ __('messages.addons_title') }}</div>
                            @foreach($ekHizmetler as $eh)
                            <div style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid #ececec;border-radius:12px;margin-bottom:8px;background:#fbfbf7;">
                                <div style="font-size:22px;">{{ $eh->ikon ?: '🧩' }}</div>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-weight:700;color:#1a1a1a;font-size:14px;">{{ $eh->ad }}</div>
                                    @if($eh->aciklama)<div style="font-size:12px;color:#888;">{{ $eh->aciklama }}</div>@endif
                                    <div style="color:#8a6d00;font-weight:800;font-size:15px;">{{ number_format((float) $eh->fiyat, 2, ',', '.') }} ₺</div>
                                </div>
                                @auth('uye')
                                <form action="{{ route('sepet.ekle') }}" method="POST" style="margin:0;flex:0 0 auto;">
                                    @csrf
                                    <input type="hidden" name="urun_id" value="{{ $eh->id }}">
                                    <input type="hidden" name="urun_tipi" value="ek-hizmet">
                                    <input type="hidden" name="fiyat" value="{{ $eh->fiyat }}">
                                    {{-- SepetController@ekle 'urun_adi' bekliyor ('name' değil) --}}
                                    <input type="hidden" name="urun_adi" value="{{ $eh->ad }}">
                                    <button type="submit" class="pd-btn-secondary" style="display:inline-block;width:auto;padding:8px 14px;font-size:13px;white-space:nowrap;">
                                        <i class="mdi mdi-basket-plus"></i> {{ __('messages.add_to_cart') }}
                                    </button>
                                </form>
                                @else
                                <a href="{{ localized_route('giris') }}" class="pd-btn-secondary" style="display:inline-block;width:auto;flex:0 0 auto;padding:8px 14px;font-size:13px;white-space:nowrap;">
                                    <i class="mdi mdi-login"></i> {{ __('messages.login') }}
                                </a>
                                @endauth
                            </div>
                            @endforeach
                        </div>
                    @endif

                    @if($kategoriler && $kategoriler->count() > 0)
                        <div class="pd-cats">
                            <div class="pd-cats-label">Kategoriler</div>
                            @foreach($kategoriler as $kat)
                                <a href="{{ localized_route('paketler.kategori', $kat->id) }}" class="pd-cat-tag">
                                    {{ \App\Helpers\TranslationHelper::translate($kat->adi) }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

{{-- ***** DEĞERLENDİRMELER (puan + yorum) ***** --}}
@include('tema.partials.paket-yorumlar', [
    'paket'        => $paket,
    'yorumlar'     => $yorumlar ?? collect([]),
    'puanOzet'     => $puanOzet ?? ['ortalama' => null, 'adet' => 0, 'dagilim' => []],
    'yorumYaptiMi' => $yorumYaptiMi ?? false,
])

{{-- Bunu Alanlar Bunları da Aldı (madde 13) --}}
@if(isset($benzerPaketler) && $benzerPaketler->count() > 0)
<div class="container" style="margin-top:10px; margin-bottom:50px;">
    <h2 style="font-size:24px; font-weight:800; color:#1a1a1a; margin:0 0 6px;">{{ __('messages.also_bought_title') }}</h2>
    <p style="color:#999; font-size:14px; margin:0 0 22px;">{{ __('messages.also_bought_sub') }}</p>
    <div class="row" style="margin:0 -6px;">
        @foreach($benzerPaketler as $yazilim)
            <div class="col-md-3 col-sm-6" style="padding:0 6px; margin-bottom:20px;">
                @include('tema.partials.paket-karti', ['yazilim' => $yazilim])
            </div>
        @endforeach
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mainImg = document.getElementById('pd-main-img');
    document.querySelectorAll('.pd-gallery-thumb').forEach(function (btn) {
        btn.addEventListener('click', function () {
            mainImg.src = this.getAttribute('data-src');
            document.querySelectorAll('.pd-gallery-thumb').forEach(function (b) { b.classList.remove('is-active'); });
            this.classList.add('is-active');
        });
    });

    // ---- Aylık paket: ay seçici ----
    var ayBtns = document.querySelectorAll('.pd-ay-btn');
    if (ayBtns.length) {
        var temelFiyat = {{ $fiyat }};                 // 1 aylık (döviz çevrimli) birim fiyat
        var sembol = @json($sembol);
        var toplamSatir = document.getElementById('pd-toplam-satiri');
        var toplamTutar = document.getElementById('pd-toplam-tutar');
        var seciliAyEl  = document.getElementById('pd-secili-ay');
        var indirimEl   = document.getElementById('pd-indirim-etiket');

        function fmt(n) {
            return sembol + n.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        ayBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var ay = parseInt(this.dataset.ay, 10);
                var ind = parseFloat(this.dataset.indirim) || 0;

                // buton görselleri
                ayBtns.forEach(function (b) {
                    b.classList.remove('is-active');
                    b.style.borderColor = '#e5e7eb';
                    b.style.background = '#fff';
                });
                this.classList.add('is-active');
                this.style.borderColor = '#4f46e5';
                this.style.background = '#eef2ff';

                // formlardaki sure input'ları
                document.querySelectorAll('.pd-sure-input').forEach(function (inp) { inp.value = ay; });

                // toplam hesap (backend ile birebir aynı formül)
                var toplam = temelFiyat * ay * (1 - ind / 100);
                seciliAyEl.textContent = ay;
                toplamTutar.textContent = fmt(toplam);
                indirimEl.textContent = ind > 0 ? ('  •  %' + ind + ' indirim uygulandı') : '';
                toplamSatir.style.display = (ay > 1) ? 'block' : 'none';
            });
        });
    }
});
</script>

@endsection