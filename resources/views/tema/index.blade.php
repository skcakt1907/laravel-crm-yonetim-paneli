@extends('layouts.master')

@section('head')
<link rel="stylesheet" href="{{ asset('tema/css/paketler.css') }}">
<link rel="stylesheet" href="{{ asset('tema/css/paketler-fix.css') }}">
<link rel="stylesheet" href="{{ asset('tema/css/paket-karti.css') }}">
@endsection

@section('content')
@php
  use Illuminate\Support\Facades\DB;
  // YENİ TABLO ÖNCELİĞİ: admin "domain_fiyatlar"a yazıyor. Ön yüz de oradan okusun.
  if (\Illuminate\Support\Facades\Schema::hasTable('domain_fiyatlar')) {
    $satirlar = DB::table('domain_fiyatlar')
        ->where('durum', 1)
        ->orderBy('uzanti', 'asc')
        ->get();
    $alanadi = (object) [
      'uzanti'   => [],
      'kayit'    => [],
      'yenileme' => [],
    ];
    foreach ($satirlar as $s) {
      $alanadi->uzanti[]   = $s->uzanti;
      $alanadi->kayit[]    = (float) $s->kayit_fiyat;
      $alanadi->yenileme[] = (float) $s->yenileme_fiyat;
    }
  } else {
    $alanadi = DB::table('alanadi')->where('id', 1)->first();
    if ($alanadi) {
      $alanadi->uzanti = json_decode($alanadi->uzanti ?? '[]', true);
      $alanadi->kayit = json_decode($alanadi->kayit ?? '[]', true);
      $alanadi->yenileme = json_decode($alanadi->yenileme ?? '[]', true);
    }
  }
  $currency = request('currency') ?? session('currency', 'TRY');
  $currency_symbol = match($currency) {
    'USD' => '$',
    'EUR' => '€',
    'AED' => 'د.إ',
    default => '₺'
  };
@endphp

<!-- ***** MODERN HERO ***** -->
{{-- Tatilim Sensin şeması: tam genişlik görsel + karartma, beyaz sola hizalı başlık,
     buton yok, arama kutusu hero'nun altına biner. Görsel admin'den (arka_plan.anasayfa);
     yüklenmemişse koyu marka gradyanı kullanılır. --}}
@php
  $heroGorsel = $arkaplan->anasayfa ?? null;
  $heroGorselYol = $heroGorsel
      ? asset('tema/uploads/arkaplan/anasayfa/' . $heroGorsel)
      : null;
@endphp
<style>
  /* white-theme.css'te "h1..h6 { color: var(--wt-heading) !important }" var;
     inline style'ı eziyordu. Koyu hero'da başlık beyaz kalmalı. */
  .hero-koyu h1 { color: #fff !important; }
  .hero-koyu .hero-marka { color: #e2e05f !important; }
  /* Arama kartının içindeki paneller kendi kartlarıydı -> kart içinde kart görünüyordu.
     Dış kart zaten beyaz; panelleri düzleştir. */
  .hero-arama-kart .ms-panel {
    background: transparent !important;
    border: 0 !important;
    box-shadow: none !important;
    border-radius: 0 !important;
    padding: 0 !important;
  }
  @media (max-width: 991px) {
    .hero-koyu { padding: 116px 0 120px !important; min-height: 0 !important; }
    .hero-koyu h1 { font-size: 30px !important; }
  }
  /* Mobilde arama formu kontrolleri alt alta tam genişlik; "Ara" butonu da
     input kadar geniş olsun (sola yaslı dar buton çirkin duruyordu). */
  @media (max-width: 640px) {
    .ms-search-form { flex-direction: column; }
    .ms-search-form > input,
    .ms-search-form > select,
    .ms-search-form > button {
      flex: 1 1 100% !important;
      width: 100% !important;
      min-width: 0 !important;
    }
    .ms-search-form > button { justify-content: center; }
  }
</style>
<section class="hero-koyu" style="position:relative; min-height:500px; display:flex; align-items:center; overflow:hidden;
               padding:128px 0 132px; background:linear-gradient(115deg,#16160e 0%,#26260f 48%,#3b3a1c 100%);">
  @if($heroGorselYol)
    <img src="{{ $heroGorselYol }}" alt="" aria-hidden="true"
         style="position:absolute; inset:0; width:100%; height:100%; object-fit:cover;">
  @endif
  {{-- Karartma: yazının okunurluğu için (onlarda rgba(0,0,0,.3)→.2).
       Görsel varsa kendisi zaten koyu -> hafif karartma yeter; yoksa gradyanın
       üstünde ekstra karartmaya gerek yok. --}}
  <div aria-hidden="true"
       style="position:absolute; inset:0; background:linear-gradient(90deg, rgba(8,8,5,{{ $heroGorselYol ? '.72' : '.45' }}) 0%, rgba(8,8,5,{{ $heroGorselYol ? '.5' : '.25' }}) 52%, rgba(8,8,5,{{ $heroGorselYol ? '.28' : '.08' }}) 100%);"></div>

  <div class="container" style="position:relative; z-index:2;">
    <div class="row align-items-center">
      <div class="col-lg-8 col-md-12">
        <span style="display:inline-flex; align-items:center; gap:8px; padding:7px 16px; border-radius:999px; background:rgba(226,224,95,.18); border:1px solid rgba(226,224,95,.35); color:#e2e05f; font-size:12px; font-weight:700; letter-spacing:.04em; margin-bottom:18px;">
          <i class="mdi mdi-flash"></i> {{ __('messages.dn_isortagim_welcome') }}
        </span>
        @php
          // Önce dil dosyasındaki çeviriyi dene; yoksa dile göre gömülü metin kullan
          $locale = app()->getLocale();
          $heroBaslik = trim(__('messages.home_hero_title'));
          if ($heroBaslik === 'messages.home_hero_title' || $heroBaslik === '') {
            $heroBaslik = match($locale) {
              'en' => 'Digital solutions that grow your business',
              'ar' => 'حلول رقمية تنمّي أعمالك',
              default => 'İşinizi büyüten dijital çözümler',
            };
          }
          $heroAlt = trim(__('messages.home_hero_subtitle'));
          if ($heroAlt === 'messages.home_hero_subtitle' || $heroAlt === '') {
            $heroAlt = match($locale) {
              'en' => 'Web software, hosting and domain registration under one roof. Fast setup, secure infrastructure, 24/7 support.',
              'ar' => 'برمجيات الويب والاستضافة وتسجيل النطاقات تحت سقف واحد. إعداد سريع، بنية تحتية آمنة، دعم على مدار الساعة.',
              default => 'Web yazılımları, hosting ve alan adı tescili tek çatı altında. Hızlı kurulum, güvenli altyapı, 7/24 destek.',
            };
          }
        @endphp
        <h1 style="color:#fff; font-size:40px; font-weight:700; line-height:1.18; margin:0 0 10px; text-shadow:0 2px 18px rgba(0,0,0,.35);">{{ $heroBaslik }}</h1>
        <div class="hero-marka" style="display:inline-block; font-size:18px; font-weight:800; letter-spacing:.02em; margin:0 0 14px; color:#e2e05f;">DN KREATİF <span style="opacity:.85">ZAMAN KAZANDIRIR</span></div>
        <p style="color:rgba(255,255,255,.88); font-size:18px; line-height:1.65; margin:0 0 24px; max-width:600px;">{{ $heroAlt }}</p>
        {{-- Buton yok: asıl çağrı arama kutusu (Tatilim Sensin şeması) --}}
        <div style="display:flex; gap:22px; flex-wrap:wrap;">
          {{-- mdi-shield-check bu font sürümünde yok (ikon boş çıkıyordu) -> mdi-shield --}}
          <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(255,255,255,.85);"><i class="mdi mdi-shield" style="color:#e2e05f; font-size:18px;"></i> {{ __('messages.domain_features_title') }}</div>
          <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(255,255,255,.85);"><i class="mdi mdi-server" style="color:#e2e05f; font-size:18px;"></i> %99.9 Uptime</div>
          <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:rgba(255,255,255,.85);"><i class="mdi mdi-headset" style="color:#e2e05f; font-size:18px;"></i> {{ __('messages.domain_support_title') }}</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ***** ARAMA (hero'nun üstüne binen kart — Tatilim Sensin şeması) ***** -->
@if($moduller && $moduller->alan1 == "1")
<section class="search-domain" style="background:transparent; padding:0 0 42px; position:relative; z-index:5;">
	<div class="container">
		<div class="row justify-content-center">
			<div class="col-lg-11 col-md-12">
				{{-- Hero'nun altına 100px binen beyaz kart --}}
				<div class="hero-arama-kart"
					 style="margin-top:-100px; background:#fff; border-radius:20px; padding:26px 28px 28px;
							box-shadow:0 8px 32px rgba(0,0,0,.12); border:1px solid #f0f0ec;">
				<div class="text-center" style="margin-bottom:18px;">
					<h2 style="color:#1a1a1a; font-size:22px; font-weight:800; margin:0 0 4px;">{{ __('messages.what_looking_for') }}</h2>
					<p style="color:#999; font-size:13.5px; margin:0;">{{ __('messages.search_all_sub') }}</p>
				</div>
{{-- Coklu arama sekmeleri (madde 12) --}}
				<div id="multiSearchTabs" style="display:flex; gap:8px; justify-content:center; margin-bottom:16px; flex-wrap:wrap;">
					<button type="button" class="ms-tab" data-panel="Paket" onclick="msTab('Paket')" style="padding:10px 22px; border-radius:999px; border:1.5px solid #b8b62e; background:#b8b62e; color:#1a1a0e; font-weight:700; font-size:14px; cursor:pointer;"><i class="mdi mdi-package-variant"></i> {{ __('messages.package') }}</button>
					<button type="button" class="ms-tab" data-panel="Domain" onclick="msTab('Domain')" style="padding:10px 22px; border-radius:999px; border:1.5px solid #e0e0d8; background:#fff; color:#444; font-weight:700; font-size:14px; cursor:pointer;"><i class="mdi mdi-web"></i> {{ __('messages.domain') }}</button>
					<button type="button" class="ms-tab" data-panel="Hosting" onclick="msTab('Hosting')" style="padding:10px 22px; border-radius:999px; border:1.5px solid #e0e0d8; background:#fff; color:#444; font-weight:700; font-size:14px; cursor:pointer;"><i class="mdi mdi-server"></i> Hosting</button>
				</div>
				<div class="ms-panel" id="msPanelPaket" style="background:#fff; border-radius:16px; padding:28px; box-shadow:0 2px 8px rgba(15,23,42,.06); border:1px solid #ececec;">
					<form action="{{ route('paketler') }}" method="GET" class="ms-search-form" style="display:flex; gap:12px; flex-wrap:wrap; align-items:stretch;">
						<input type="text" name="kelime" placeholder="{{ __('messages.package_search_placeholder') }}" style="flex:1; min-width:200px; padding:14px 18px; font-size:15px; border:1px solid #e0e0d8; border-radius:11px; background:#f7f7f4; color:#1a1a1a; outline:none;">
						<button type="submit" style="flex:0 0 auto; padding:14px 34px; font-size:15px; font-weight:700; background:#b8b62e; color:#1a1a0e; border:none; border-radius:11px; cursor:pointer;"><i class="mdi mdi-magnify"></i> {{ __('messages.search_package_button') }}</button>
					</form>
				</div>
				<div class="ms-panel" id="msPanelHosting" style="display:none; background:#fff; border-radius:16px; padding:28px; box-shadow:0 2px 8px rgba(15,23,42,.06); border:1px solid #ececec;">
					<form action="{{ route('hosting') }}" method="GET" class="ms-search-form" style="display:flex; gap:12px; flex-wrap:wrap; align-items:stretch;">
						<input type="text" name="kelime" placeholder="Hosting paketi ara..." style="flex:1; min-width:200px; padding:14px 18px; font-size:15px; border:1px solid #e0e0d8; border-radius:11px; background:#f7f7f4; color:#1a1a1a; outline:none;">
						<button type="submit" style="flex:0 0 auto; padding:14px 34px; font-size:15px; font-weight:700; background:#b8b62e; color:#1a1a0e; border:none; border-radius:11px; cursor:pointer;"><i class="mdi mdi-magnify"></i> {{ __('messages.search_hosting_button') }}</button>
					</form>
				</div>
				<div class="ms-panel search-domain-content" id="msPanelDomain" style="display:none; background: #ffffff; border-radius: 16px; padding: 28px; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06); border: 1px solid #ececec;">
					<form action="" id="domainForm" class="ms-search-form" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: stretch;">
						@csrf
						<input type="text" name="alanadi" id="alanadi" placeholder="{{ __('messages.enter_domain') }}" required style="flex: 1; min-width: 200px; padding: 14px 18px; font-size: 15px; border: 1px solid #e0e0d8; border-radius: 11px; background: #f7f7f4; color: #1a1a1a; outline: none; transition: all 0.3s ease;" onfocus="this.style.borderColor='#b8b62e'; this.style.background='#fff';" onblur="this.style.borderColor='#e0e0d8'; this.style.background='#f7f7f4';">
						<select name="uzanti" id="uzanti" style="flex: 0 0 180px; padding: 14px 18px; font-size: 15px; border: 1px solid #e0e0d8; border-radius: 11px; background: #f7f7f4; color: #1a1a1a; outline: none; cursor: pointer; transition: all 0.3s ease;" onfocus="this.style.borderColor='#b8b62e'; this.style.background='#fff';" onblur="this.style.borderColor='#e0e0d8'; this.style.background='#f7f7f4';">
							@if($alanadi && is_array($alanadi->uzanti))
								@foreach($alanadi->uzanti as $k => $v)
									@php $degisken = explode(".", $v); @endphp
									<option value="{{ $degisken[1] }}{{ isset($degisken[2]) && $degisken[2] ? '.' : '' }}{{ $degisken[2] ?? '' }}" style="background: #ffffff; color: #1a1a1a;">{{ $v }}</option>
								@endforeach
							@endif
						</select>
						<button class="bttn btn-fill" type="submit" id="domainSorgula" style="flex: 0 0 auto; padding: 14px 34px; font-size: 15px; font-weight: 700; background: #b8b62e; color: #1a1a0e; border: none; border-radius: 11px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 6px 18px rgba(184, 182, 46, 0.3);" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';">{{ __('messages.query') }}</button>
					</form>
				</div>
				<div class="domain-type" id="domainTypeGrid" style="display: none; grid-template-columns: repeat(5, 1fr); gap: 12px; margin-top: 22px;">
					@if($alanadi && is_array($alanadi->uzanti))
						@foreach($alanadi->uzanti as $k => $v)
							@if($k > 4) @break @endif
							@php
								$basePrice = $alanadi->kayit[$k] ?? 0;
								$displayPrice = \App\Helpers\DovizKuruHelper::fiyatGoster($basePrice, $currency);
							@endphp
							<div class="single-domain-type" style="background: #ffffff; border-radius: 12px; padding: 16px; text-align: center; border: 1px solid #ececec; transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);" onmouseover="this.style.transform='translateY(-4px)'; this.style.borderColor='rgba(184, 182, 46, 0.35)'; this.style.boxShadow='0 8px 20px rgba(184, 182, 46, 0.12)';" onmouseout="this.style.transform='translateY(0)'; this.style.borderColor='#ececec'; this.style.boxShadow='0 1px 3px rgba(15, 23, 42, 0.04)';">
								<h3 style="color: #1a1a1a; font-size: 15px; font-weight: 700; margin: 0 0 6px 0;">{{ $v }}/</h3>
								<span style="color: #b8b62e; font-size: 17px; font-weight: 700;">{{ $displayPrice }} {{ $currency_symbol }}</span>
							</div>
						@endforeach
					@endif
				</div>
				<div class="domainBilgileri" id="domainBilgileri"></div>
				<script>
				function msTab(name){
					['Paket','Domain','Hosting'].forEach(function(n){
						var panel=document.getElementById('msPanel'+n);
						if(panel) panel.style.display = (n===name)?'block':'none';
					});
					var grid=document.getElementById('domainTypeGrid');
					if(grid) grid.style.display = (name==='Domain')?'grid':'none';
					document.querySelectorAll('#multiSearchTabs .ms-tab').forEach(function(b){
						var aktif = (b.getAttribute('data-panel')===name);
						b.style.background = aktif ? '#b8b62e' : '#fff';
						b.style.color = aktif ? '#1a1a0e' : '#444';
						b.style.borderColor = aktif ? '#b8b62e' : '#e0e0d8';
					});
				}
				</script>
				</div>{{-- /hero-arama-kart --}}
			</div>
		</div>
	</div>
</section>
@endif

{{-- ***** VİTRİN ŞERİDİ — ortak stil + kaydırma (hem Öne Çıkan hem kategori vitrinleri kullanır) ***** --}}
<style>
    .vitrin-strip::-webkit-scrollbar { height:6px; }
    .vitrin-strip::-webkit-scrollbar-thumb { background:#dcdcd2; border-radius:99px; }
    .vitrin-strip::-webkit-scrollbar-track { background:transparent; }
    .vitrin-kart:hover { transform:translateY(-4px); box-shadow:0 10px 26px rgba(0,0,0,.10) !important; border-color:rgba(184,182,46,.45) !important; }
    .vitrin-ok:hover { background:#f7f7f0 !important; border-color:#b8b62e !important; color:#1a1a0e !important; }
    .vitrin-ok:disabled { opacity:.32; cursor:default; }
    @media (prefers-reduced-motion: reduce) {
        .vitrin-strip { scroll-behavior:auto; }
        .vitrin-kart { transition:none !important; }
    }
</style>
<script>
function vitrinKaydir(id, yon){
    var s = document.getElementById(id);
    if(!s) return;
    s.scrollBy({ left: yon * (s.clientWidth * 0.8), behavior: 'smooth' });
}
// Şeridin başında/sonunda okları söndür
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.vitrin-strip').forEach(function(s){
        var geri = document.querySelector('[data-ok-geri="' + s.id + '"]');
        var ileri = document.querySelector('[data-ok-ileri="' + s.id + '"]');
        if(!geri || !ileri) return;
        function tazele(){
            var son = s.scrollWidth - s.clientWidth - 2;
            geri.disabled  = s.scrollLeft <= 2;
            ileri.disabled = s.scrollLeft >= son;
        }
        s.addEventListener('scroll', tazele, { passive:true });
        window.addEventListener('resize', tazele);
        tazele();
    });
});
</script>

{{-- Nurseli Hanım: generic "Öne Çıkan" şeridi kaldırıldı; yerine aşağıdaki 4 kategori
     bölümü geçti (ilki "Öne Çıkan Sosyal Medya Paketleri"). --}}

<!-- ***** KATEGORİ VİTRİNLERİ (4 paket bölümü — Sosyal Medya / Dijital Pazarlama / Prodüksiyon / Web) ***** -->
{{-- Stil + vitrinKaydir() yukarıda, Öne Çıkan şeridiyle ortak tanımlı. --}}
@if(!empty($vitrinKategoriler))
    @foreach($vitrinKategoriler as $i => $vitrin)
        {{-- Zemin dönüşümlü: beyaz / kırık beyaz (Tatilim Sensin'de white / gray-50) --}}
        @include('tema.partials.vitrin-carousel', ['vitrin' => $vitrin, 'zemin' => $i % 2 ? '#fbfbf7' : '#ffffff'])
    @endforeach
@endif

<!-- ***** HAKKIMIZDA ***** -->
@if($moduller && $moduller->alan3 == "1" && $kurumsal)
<div class="page-content parallax getready" style="background-image:url({{ asset('tema/uploads/arkaplan/footer/'.($arkaplan->footer ?? 'bg.jpg')) }})">
    <div class="container">
        <div class="row">
            <div class="col-md-12 wow fadeInUp" data-wow-delay="0.3s">
				<div class="row">
					<div class="col-lg-8">
						<div class="column-support-txt">
							<div class="column-support-title">{{ __('messages.about_us') }}</div>
							<div class="column-support-subtitle">
						{{ \App\Helpers\TranslationHelper::translate($kurumsal->{'kisa_'.app()->getLocale()} ?? $kurumsal->kisa ?? '') }}
						</div>
						</div>
					</div>
					<div class="col-lg-4 pt-3">
						<div class="btn-floats mt-5">
							<a href="{{ route('sayfa.detay', $kurumsal->seo) }}" class="btn btn-2 btn-default-pink-fill">{{ __('messages.read_more') }}</a>
						</div>
					</div>
				</div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- ***** HOSTİNGLER ***** -->
@if($moduller && $moduller->alan4 == "1")
@foreach($hosting_kategoriler as $host_kategori)
<section class="slick sec-normal pt-4 wow fadeInUp" data-wow-delay="0.3s">
		<div class="col-sm-12 text-center">
			<h2 class="section-heading" style="">{{ \App\Helpers\TranslationHelper::translate($host_kategori->adi) }}</h2>
			<p class="section-subheading" style="">{{ \App\Helpers\TranslationHelper::translate($host_kategori->kisa) }}</p>
		</div>
    <div class="slider">
		@foreach($host_kategori->hostingler as $hosting)
        <div class="plan-container">
            <div class="wrapper text-center" style="background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,.06); border:1px solid rgba(15,23,42,.06); transition:all .3s ease;" onmouseover="this.style.transform='translateY(-6px)'; this.style.boxShadow='0 16px 40px rgba(184,182,46,.18)'; this.style.borderColor='rgba(184,182,46,.35)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px rgba(15,23,42,.06)'; this.style.borderColor='rgba(15,23,42,.06)';">
				<div class="top-content p-3" style="padding:28px 20px !important;">
					<img class="svg mb-3" src="{{ asset('tema/fonts/svg/dedicated.svg') }}" alt="linux" style="height:56px;">
					<div class="title" style="color:#0f172a; font-size:20px; font-weight:800; margin-bottom:4px;">{{ \App\Helpers\TranslationHelper::translate($hosting->adi) }}</div>
					<div class="fromer" style="color:#94a3b8; font-size:13px; font-weight:600; margin-bottom:14px;">{{ \App\Helpers\TranslationHelper::translate($host_kategori->adi) }}</div>
					<div class="price" style="color:#0f172a; font-size:30px; font-weight:900; margin-bottom:18px;">
						@php
							$currency = request('currency') ?? session('currency', 'TRY');
							$fiyat = \App\Helpers\DovizKuruHelper::fiyatGoster($hosting->tutar, $currency);
							$sembol = match($currency) {
								'USD' => '$',
								'EUR' => '€',
								'AED' => 'د.إ',
								default => '₺'
							};
						@endphp
						{{ $sembol }}{{ $fiyat }} <span class="period" style="color:#94a3b8; font-size:14px; font-weight:600;">/{{ $hosting->zmnt == 0 ? __('messages.monthly') : ($hosting->zmnt == 1 ? __('messages.yearly') : __('messages.quarterly')) }}</span>
					</div>
					@if($moduller && $moduller->alan6 == "1")
					<a href="{{ route('hosting.satinal', $hosting->id) }}" style="display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:12px 28px; font-size:15px; font-weight:700; background:#b8b62e; color:#1a1a0e; border:none; border-radius:10px; text-decoration:none; transition:all .25s ease; box-shadow:0 6px 18px rgba(184,182,46,.3);"><i class="mdi mdi-basket" style="font-size: 15px;"></i> {{ __('messages.buy_now') }}</a>
					@endif
				</div>
				<ul class="list-info p-3 text-left" style="background:#15212a; color:#fff; margin:0; padding:22px 24px !important; list-style:none;">
					@php $ozellikler = explode(',', $hosting->ozellikler); @endphp
					@foreach($ozellikler as $ozellik)
						<li class="pt-1 pb-1" style="color:#e2e8f0; padding:6px 0;"><span class="mdi mdi-check-circle mr-2" style="color:#b8b62e;"></span> {{ trim($ozellik) }}</li>
					@endforeach
				</ul>
			</div>
        </div>
		@endforeach
    </div>
</section>
@endforeach
@endif

<!-- ***** REFERANSLAR ***** -->
@if($moduller && $moduller->alan10 == "1")
<section class="services sec-normal  sec-bg2 wow fadeInUp" data-wow-delay="0.3s" style="padding: 80px 0 !important;">
	<div class="container">
		<div class="service-wrap">
			<div class="row">
		<div class="col-sm-12 text-center">
			<h2 class="section-heading" style="">{{ __('messages.our_references_title') }}</h2>
			<p class="section-subheading" style="">{{ __('messages.our_successful_projects') }}</p>
		</div>
			</div>
			<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; width: 100%; margin-top: 40px;">
				@foreach($referanslar as $referans)
				<div style="width: 100% !important; min-width: 0 !important; padding: 0 !important; margin-bottom: 0 !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; box-sizing: border-box !important;">
					<a href="{{ route('referans.detay', $referans->seo) }}" style="text-decoration: none !important; display: block !important;">
						<div style="background: #fff; border-radius: 14px; padding: 0; overflow: hidden; min-height: 400px !important; display: flex !important; flex-direction: column !important; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.06); transition: all 0.3s ease !important; visibility: visible !important; opacity: 1 !important; position: relative !important; margin: 0 !important; width: 100% !important; height: auto !important;">
							@php
								$resim_yolu = $referans->resim ? (str_starts_with($referans->resim, 'tema/') ? $referans->resim : 'tema/uploads/referanslar/'.$referans->resim) : null;
								$referans_resim = $resim_yolu ? asset($resim_yolu) : null;
							@endphp
							
							<div style="position: relative; height: 250px; overflow: hidden; background: #eef0f4; width: 100%; margin: 0 !important; padding: 0 !important; border-radius: 17px 17px 0 0 !important;">
								@if($referans->resim && file_exists(public_path($resim_yolu)))
								<img src="{{ $referans_resim }}" alt="{{ \App\Helpers\TranslationHelper::translate($referans->adi) }}" style="width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; margin: 0 !important; padding: 0 !important; border-radius: 17px 17px 0 0 !important;" onerror="this.src='{{ asset('tema/img/noimage.png') }}'; this.onerror=null; this.style.display='block';">
								@else
								<div style="width: 100% !important; height: 100% !important; display: flex !important; align-items: center !important; justify-content: center !important; background: #eef0f4 !important; margin: 0 !important; padding: 0 !important; border-radius: 17px 17px 0 0 !important;">
									<i class="mdi mdi-image" style="font-size: 48px !important; color: #94a3b8 !important;"></i>
								</div>
								@endif
								<div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.3) 100%); opacity: 0; transition: opacity 0.3s ease;"></div>
							</div>
							
							<div style="padding: 25px; background: #fff; display: flex; flex-direction: column; border-top: 1px solid rgba(0,0,0,0.06); flex: 1; min-height: 150px;">
								<h3 style="color: #0f172a; font-size: 20px; font-weight: 700; margin-bottom: 10px; line-height: 1.3; visibility: visible; display: block; margin-top: 0; flex: 1;">
								{{ \App\Helpers\TranslationHelper::translate($referans->adi) }}
							</h3>
								
								@if($referans->kisa)
							<p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 15px; flex: 1;">
								{{ Str::limit(strip_tags(\App\Helpers\TranslationHelper::translate($referans->kisa)), 80) }}
							</p>
							@endif
								
								<div style="display: flex !important; justify-content: flex-end !important; align-items: center !important; margin-top: auto !important; padding-top: 15px !important;">
									<span style="display: inline-flex !important; align-items: center !important; gap: 8px !important; color: #b8b62e !important; font-size: 14px !important; font-weight: 600 !important;">
										{{ __('messages.view_project') }} <i class="mdi mdi-arrow-right" style="font-size: 12px;"></i>
									</span>
								</div>
							</div>
						</div>
					</a>
				</div>
				@endforeach
			</div>
		</div>
	</div>
</section>	
@endif

<!-- ***** BLOG ***** -->
@if($moduller && $moduller->alan5 == "1")
<section class="services blog sec-normal sec-bg3 wow fadeInUp" data-wow-delay="0.3s">
	<div class="container">
		<div class="service-wrap">
			<div class="row">
				<div class="col-sm-12 text-center">
					<h2 class="section-heading text-white" style="">{{ __('messages.blog') }}</h2>
					<p class="section-subheading text-white" style="">{{ __('messages.latest_blog_title') }}</p>
				</div>
			</div>
			<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; width: 100%; margin-top: 40px;">
				@foreach($bloglar as $blog)
				<div style="width: 100% !important; min-width: 0 !important; padding: 0 !important; margin-bottom: 0 !important; display: block !important; visibility: visible !important; opacity: 1 !important; position: relative !important; box-sizing: border-box !important;">
					<div style="background: #fff; border-radius: 14px; padding: 0; overflow: hidden; min-height: 450px !important; display: flex !important; flex-direction: column !important; box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.06); transition: all 0.3s ease !important; visibility: visible !important; opacity: 1 !important; position: relative !important; margin: 0 !important; width: 100% !important; height: auto !important;">
						@php
							$blog_resim = asset('tema/uploads/bloglar/'.$blog->resim);
						@endphp
						
						<div style="position: relative; height: 250px; overflow: hidden; background: #eef0f4; width: 100%; margin: 0 !important; padding: 0 !important; border-radius: 17px 17px 0 0 !important;">
							@if($blog->resim && file_exists(public_path('tema/uploads/bloglar/'.$blog->resim)))
							<img src="{{ $blog_resim }}" alt="{{ $blog->adi }}" style="width: 100% !important; height: 100% !important; object-fit: cover !important; display: block !important; margin: 0 !important; padding: 0 !important; border-radius: 17px 17px 0 0 !important;" onerror="this.src='{{ asset('tema/img/noimage.png') }}'; this.onerror=null; this.style.display='block';">
							@else
							<div style="width: 100% !important; height: 100% !important; display: flex !important; align-items: center !important; justify-content: center !important; background: #eef0f4 !important; margin: 0 !important; padding: 0 !important; border-radius: 17px 17px 0 0 !important;">
								<i class="mdi mdi-image" style="font-size: 48px !important; color: #94a3b8 !important;"></i>
							</div>
							@endif
							<div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: linear-gradient(to bottom, transparent 0%, rgba(0,0,0,0.3) 100%); opacity: 0; transition: opacity 0.3s ease;"></div>
						</div>
						
						<div style="padding: 25px; background: #fff; display: flex; flex-direction: column; border-top: 1px solid rgba(0,0,0,0.06); flex: 1; min-height: 200px;">
							<h3 style="color: #0f172a; font-size: 20px; font-weight: 700; margin-bottom: 15px; line-height: 1.3; visibility: visible; display: block; margin-top: 0; flex: 1;">
							<a href="{{ route('blog.detay', $blog->seo) }}" style="color: #0f172a; text-decoration: none; display: block;">{{ $blog->adi }}</a>
						</h3>
							
							<p style="color: #475569; font-size: 14px; line-height: 1.6; margin-bottom: 15px; flex: 1;">
								{{ Str::limit(strip_tags($blog->aciklama), 100) }}
							</p>
							
							<div style="display: flex !important; justify-content: space-between !important; align-items: center !important; margin-top: auto !important; padding-top: 15px !important; border-top: 1px solid rgba(0,0,0,0.06) !important;">
								<div style="display: flex !important; align-items: center !important; gap: 8px !important;">
									<i class="mdi mdi-calendar" style="color: #b8b62e; font-size: 14px;"></i>
									<span style="color: #94a3b8; font-size: 12px;">
										{{ date('d.m.Y', strtotime($blog->tarih)) }}
									</span>
								</div>
								
								<a href="{{ route('blog.detay', $blog->seo) }}" style="padding: 10px 20px; font-size: 13px; text-align: center; background: #b8b62e; color: #1a1a0e; border: none; border-radius: 10px; text-decoration: none; display: inline-flex !important; align-items: center !important; justify-content: center !important; font-weight: 700 !important; transition: all 0.3s ease !important;">
									{{ __('messages.read_more') }} <i class="mdi mdi-arrow-right ml-2" style="font-size: 11px;"></i>
								</a>
							</div>
						</div>
					</div>
				</div>
				@endforeach
			</div>
		</div>
	</div>
</section>
@endif

<!-- ***** BİLGİLENDİRME ***** -->
@if($moduller && $moduller->alan11 == "1")
@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/iletisim.css') }}">
<style>
  /* Anasayfa bölümlerini admin açık tonuna çek (sec-bg2/sec-bg3 koyu ezilir) */
  section.services.sec-bg2 { background:#ffffff !important; }
  section.services.sec-bg3, section.services.blog { background:#f0f0ec !important; }
  /* Koyu zemine göre beyaz yazılmış başlıkları koyuya çevir */
  .section-heading.text-white, .section-subheading.text-white { color:#1a1a1a !important; }
  .section-subheading.text-white { color:#777 !important; }
  /* Bölüm başlıkları admin tonunda vurgulu */
  .section-heading { color:#1a1a1a !important; font-weight:800 !important; }
  .section-subheading { color:#999 !important; }
</style>
@endpush
<section class="help-section" style="padding: 60px 0;">
	<div class="container">
		<div class="row">
			<div class="col-sm-12 col-md-6 col-lg-4 mb-4">
				<a href="{{ localized_route('hesabim') }}" class="help-card-modern">
					<div class="help-card-icon">
						<img class="svg ico" src="{{ asset('tema/fonts/svg/livechat.svg') }}" height="65" alt="">
					</div>
					<div class="help-card-content">
						<h3 class="help-card-title">{{ __('messages.live_support') }}</h3>
						<p class="help-card-description">{{ __('messages.live_support_desc') }}</p>
					</div>
					<div class="help-card-arrow">
						<i class="mdi mdi-arrow-right"></i>
					</div>
				</a>
			</div>
			<div class="col-sm-12 col-md-6 col-lg-4 mb-4">
				<a href="mailto:{{ ($ayarlar->email ?? 'info@example.com') }}" class="help-card-modern">
					<div class="help-card-icon">
						<img class="svg ico" src="{{ asset('tema/fonts/svg/emailopen.svg') }}" height="65" alt="">
					</div>
					<div class="help-card-content">
						<h3 class="help-card-title">{{ __('messages.email_support') }}</h3>
						<p class="help-card-description">{{ ($ayarlar->email ?? 'info@example.com') }}</p>
					</div>
					<div class="help-card-arrow">
						<i class="mdi mdi-arrow-right"></i>
					</div>
				</a>
			</div>
			<div class="col-sm-12 col-md-6 col-lg-4 mb-4">
				<a href="{{ localized_route('iletisim') }}" class="help-card-modern">
					<div class="help-card-icon">
						<img class="svg ico" src="{{ asset('tema/fonts/svg/book.svg') }}" height="65" alt="">
					</div>
					<div class="help-card-content">
						<h3 class="help-card-title">{{ __('messages.knowledge_base') }}</h3>
						<p class="help-card-description">{{ __('messages.knowledge_base_desc') }}</p>
					</div>
					<div class="help-card-arrow">
						<i class="mdi mdi-arrow-right"></i>
					</div>
				</a>
			</div>
		</div>
	</div>
</section>
@endif

{{-- ===== FOOTER ÜSTÜ CTA + LOKASYONLAR (Duty tarzı) ===== --}}
<section style="background:#f0f0ec; padding:60px 0;">
  <div class="container">
    {{-- Direkt İletişim CTA --}}
    <div style="background:#1a2332; border:1px solid #2a3242; border-radius:18px; padding:32px; display:flex; flex-wrap:wrap; align-items:center; gap:24px; box-shadow:0 4px 18px rgba(15,23,42,.18); margin-bottom:36px;">
      <div style="width:60px; height:60px; flex-shrink:0; border-radius:14px; background:rgba(184,182,46,.18); display:flex; align-items:center; justify-content:center;">
        <i class="mdi mdi-message-text" style="font-size:30px; color:#b8b62e;"></i>
      </div>
      <div style="flex:1; min-width:240px;">
        <h3 style="color:#ffffff !important; font-size:22px; font-weight:800; margin:0 0 6px;">{{ __('messages.direct_contact_title') }}</h3>
        <p style="color:#c4ccd8 !important; font-size:14px; margin:0; line-height:1.6;">{{ __('messages.direct_contact_sub') }}</p>
      </div>
      <div style="display:flex; gap:10px; flex-wrap:wrap;">
        <a href="https://wa.me/902129930218" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; gap:8px; padding:12px 22px; border-radius:11px; background:#25D366; color:#fff; font-weight:700; font-size:14px; text-decoration:none; border:none; box-shadow:0 4px 12px rgba(37,211,102,.30); transition:all .25s ease;" onmouseover="this.style.background='#1ebe5a';" onmouseout="this.style.background='#25D366';">
          <i class="mdi mdi-whatsapp" style="font-size:18px; color:#fff;"></i> WhatsApp
        </a>
        <a href="mailto:{{ $ayarlar->firma_email ?? 'isortagim@ornek.com' }}" style="display:inline-flex; align-items:center; gap:8px; padding:12px 22px; border-radius:11px; background:#b8b62e; color:#1a1a0e; font-weight:700; font-size:14px; text-decoration:none; border:none; box-shadow:0 4px 12px rgba(184,182,46,.30); transition:all .25s ease;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';">
          <i class="mdi mdi-email" style="font-size:18px;"></i> {{ $ayarlar->firma_email ?? 'isortagim@ornek.com' }}
        </a>
      </div>
    </div>

    {{-- 3 Lokasyon kartı --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">

      {{-- Konum 1: Dubai (Merkez) --}}
      <div style="background:#fff; border:1px solid #ececec; border-radius:14px; padding:26px; box-shadow:0 2px 8px rgba(0,0,0,0.05); transition:all .25s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)';">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
          <span style="font-size:11px; color:#b8b62e; font-weight:800; letter-spacing:.08em;">DUBAİ / MEYDAN FREEZONE</span>
        </div>
        <div style="font-size:12px; color:#94a3b8; font-weight:600; letter-spacing:.05em; margin-bottom:18px;">MERKEZ</div>
        <div style="display:flex; gap:12px; margin-bottom:18px;">
          <div style="width:36px; height:36px; flex-shrink:0; border-radius:10px; background:rgba(184,182,46,.12); display:flex; align-items:center; justify-content:center;">
            <i class="mdi mdi-map-marker" style="font-size:19px; color:#9a981f;"></i>
          </div>
          <div style="color:#475569; font-size:14px; line-height:1.6;">
            The Meydan Hotel<br>
            Grandstand 6th floor<br>
            Meydan Road, Nad Al Sheba<br>
            Dubai U.A.E.
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; padding:10px 0; border-top:1px solid #f0f0ec;">
          <i class="mdi mdi-email-outline" style="color:#94a3b8; font-size:16px;"></i>
          <span style="color:#475569; font-size:13px;">info@ornek.com</span>
        </div>
        <div style="display:flex; align-items:center; gap:10px; padding:10px 0; border-top:1px solid #f0f0ec;">
          <i class="mdi mdi-phone-outline" style="color:#94a3b8; font-size:16px;"></i>
          <span style="color:#475569; font-size:13px;">+971 52 748 23 05</span>
        </div>
      </div>

      {{-- Konum 2: İstanbul (Şube) --}}
      <div style="background:#fff; border:1px solid #ececec; border-radius:14px; padding:26px; box-shadow:0 2px 8px rgba(0,0,0,0.05); transition:all .25s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)';">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
          <span style="font-size:11px; color:#b8b62e; font-weight:800; letter-spacing:.08em;">İSTANBUL / ŞİŞLİ</span>
        </div>
        <div style="font-size:12px; color:#94a3b8; font-weight:600; letter-spacing:.05em; margin-bottom:18px;">ŞUBE</div>
        <div style="display:flex; gap:12px; margin-bottom:18px;">
          <div style="width:36px; height:36px; flex-shrink:0; border-radius:10px; background:rgba(184,182,46,.12); display:flex; align-items:center; justify-content:center;">
            <i class="mdi mdi-map-marker" style="font-size:19px; color:#9a981f;"></i>
          </div>
          <div style="color:#475569; font-size:14px; line-height:1.6;">
            {{ $ayarlar->firma_adres ?? 'Fulya Mah. Bahçeler Sok. No: 9/A Kat: 3 D: 7' }}<br>
            Şişli / İstanbul
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; padding:10px 0; border-top:1px solid #f0f0ec;">
          <i class="mdi mdi-email-outline" style="color:#94a3b8; font-size:16px;"></i>
          <span style="color:#475569; font-size:13px;">{{ $ayarlar->firma_email ?? 'isortagim@ornek.com' }}</span>
        </div>
        <div style="display:flex; align-items:center; gap:10px; padding:10px 0; border-top:1px solid #f0f0ec;">
          <i class="mdi mdi-phone-outline" style="color:#94a3b8; font-size:16px;"></i>
          <span style="color:#475569; font-size:13px;">{{ $ayarlar->firma_telefon ?? '0 (212) 993 02 18' }}</span>
        </div>
        <div style="display:flex; align-items:center; gap:10px; padding:10px 0; border-top:1px solid #f0f0ec;">
          <i class="mdi mdi-phone-outline" style="color:#94a3b8; font-size:16px;"></i>
          <span style="color:#475569; font-size:13px;">0850 307 95 48</span>
        </div>
      </div>

      {{-- Konum 3: Marmaris (Şube) --}}
      <div style="background:#fff; border:1px solid #ececec; border-radius:14px; padding:26px; box-shadow:0 2px 8px rgba(0,0,0,0.05); transition:all .25s ease;" onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 24px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 8px rgba(0,0,0,0.05)';">
        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
          <span style="font-size:11px; color:#b8b62e; font-weight:800; letter-spacing:.08em;">MUĞLA / MARMARİS</span>
        </div>
        <div style="font-size:12px; color:#94a3b8; font-weight:600; letter-spacing:.05em; margin-bottom:18px;">ŞUBE</div>
        <div style="display:flex; gap:12px; margin-bottom:18px;">
          <div style="width:36px; height:36px; flex-shrink:0; border-radius:10px; background:rgba(184,182,46,.12); display:flex; align-items:center; justify-content:center;">
            <i class="mdi mdi-map-marker" style="font-size:19px; color:#9a981f;"></i>
          </div>
          <div style="color:#475569; font-size:14px; line-height:1.6;">
            Armutalan Mah. Şehit<br>
            Ahmet Benler Cad. 310.<br>
            Sok: Mavikent Sitesi<br>
            No:10 Marmaris/Muğla
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:10px; padding:10px 0; border-top:1px solid #f0f0ec;">
          <i class="mdi mdi-email-outline" style="color:#94a3b8; font-size:16px;"></i>
          <span style="color:#475569; font-size:13px;">info@ornek.com</span>
        </div>
        <div style="display:flex; align-items:center; gap:10px; padding:10px 0; border-top:1px solid #f0f0ec;">
          <i class="mdi mdi-phone-outline" style="color:#94a3b8; font-size:16px;"></i>
          <span style="color:#475569; font-size:13px;">0850 307 95 48</span>
        </div>
      </div>

    </div>
  </div>
</section>
@endsection

@push('styles')
<style>
	/* Domain hero: mobilde padding azalt */
	@media (max-width: 768px) {
		section.search-domain.domain-hero {
			padding: 140px 0 70px 0 !important;
			min-height: unset !important;
		}
		section.search-domain.domain-hero .domain-hero-head h1 {
			font-size: 32px !important;
		}
		section.search-domain.domain-hero .search-domain-content {
			padding: 22px !important;
		}
	}

	/* Navbar paketler hover efektini kaldır */
	.nav-menu .main-menu > .menu-item.no-hover-effect:hover > a,
	.nav-menu .main-menu > .menu-item.no-hover-effect > a:hover,
	.nav-menu .main-menu > .menu-item > a[href*="paketler"]:hover,
	.nav-menu .main-menu > .menu-item:has(a[href*="paketler"]):hover > a {
		color: inherit !important;
		background-color: transparent !important;
		transform: none !important;
	}
	
	.nav-menu .main-menu > .menu-item.no-hover-effect:hover > .sub-menu,
	.nav-menu .main-menu > .menu-item:has(a[href*="paketler"]):hover > .sub-menu {
		opacity: 0 !important;
		visibility: hidden !important;
		display: none !important;
	}

	
	/* Domain kartları kare yap */
	.domain-type .single-domain-type {
		width: 100% !important;
		height: 120px !important;
		max-width: 100% !important;
		aspect-ratio: unset !important;
	}
	
	/* Blog kartları responsive */
	@media (max-width: 768px) {
		section.blog div[style*="grid-template-columns"] {
			grid-template-columns: 1fr !important;
			gap: 20px !important;
		}
	}
	
	@media (min-width: 769px) and (max-width: 1024px) {
		section.blog div[style*="grid-template-columns"] {
			grid-template-columns: repeat(2, 1fr) !important;
			gap: 25px !important;
		}
	}
	
	@media (min-width: 1025px) {
		section.blog div[style*="grid-template-columns"] {
			grid-template-columns: repeat(3, 1fr) !important;
		}
	}
	
	/* Referans kartları responsive */
	@media (max-width: 768px) {
		section.services.sec-bg2 div[style*="grid-template-columns"] {
			grid-template-columns: 1fr !important;
			gap: 20px !important;
		}
	}
	
	@media (min-width: 769px) and (max-width: 1024px) {
		section.services.sec-bg2 div[style*="grid-template-columns"] {
			grid-template-columns: repeat(2, 1fr) !important;
			gap: 25px !important;
		}
	}
	
	@media (min-width: 1025px) {
		section.services.sec-bg2 div[style*="grid-template-columns"] {
			grid-template-columns: repeat(3, 1fr) !important;
		}
	}
	
	/* Referans kartları hover efekti */
	section.services.sec-bg2 a[href*="referans"] > div:hover {
		transform: translateY(-10px) !important;
		box-shadow: 0 30px 80px rgba(0, 0, 0, 0.9) !important;
		border-color: rgba(184, 182, 46, 0.8) !important;
	}
	
	section.services.sec-bg2 a[href*="referans"] > div:hover img {
		transform: scale(1.1) !important;
	}
	
	section.services.sec-bg2 a[href*="referans"] > div:hover > div:first-child > div:last-child {
		opacity: 1 !important;
	}
	
	/* Tüm paketleri görmek butonu hover */
	a[href*="paketler"]:hover {
		transform: translateY(-3px) !important;
		box-shadow: 0 15px 40px rgba(184, 182, 46, 0.4) !important;
	}
	
	/* Navbar 'Paketler' menü ögesi için TÜM hover efektlerini kaldır */
	.nav-menu .main-menu > .menu-item.no-hover-effect > a,
	.nav-menu .main-menu > .menu-item.no-hover-effect > a:hover,
	.nav-menu .main-menu > .menu-item > a[href*="paketler"],
	.nav-menu .main-menu > .menu-item > a[href*="paketler"]:hover {
		color: inherit !important;
		background: transparent !important;
		transform: none !important;
		box-shadow: none !important; /* genel paketler hover box-shadow'unu iptal et */
		text-shadow: none !important;
	}
	
	/* Paketler linkinin çizgi/underline animasyonlarını da kapat */
	.nav-menu .main-menu > .menu-item.no-hover-effect > a::before,
	.nav-menu .main-menu > .menu-item.no-hover-effect > a::after,
	.nav-menu .main-menu > .menu-item > a[href*="paketler"]::before,
	.nav-menu .main-menu > .menu-item > a[href*="paketler"]::after {
		content: none !important;
		display: none !important;
		background: none !important;
	}
	
	/* Koyu arka planlı input'larda placeholder rengini beyaz yap */
	input[style*="background: rgba(255, 255, 255, 0.1)"]::placeholder,
	input[style*="background:rgba(255, 255, 255, 0.1)"]::placeholder {
		color: #94a3b8 !important;
		opacity: 1 !important;
	}
	
	input[style*="background: rgba(255, 255, 255, 0.1)"]::-webkit-input-placeholder,
	input[style*="background:rgba(255, 255, 255, 0.1)"]::-webkit-input-placeholder {
		color: #94a3b8 !important;
		opacity: 1 !important;
	}
	
	input[style*="background: rgba(255, 255, 255, 0.1)"]::-moz-placeholder,
	input[style*="background:rgba(255, 255, 255, 0.1)"]::-moz-placeholder {
		color: #94a3b8 !important;
		opacity: 1 !important;
	}
	
	input[style*="background: rgba(255, 255, 255, 0.1)"]:-ms-input-placeholder,
	input[style*="background:rgba(255, 255, 255, 0.1)"]:-ms-input-placeholder {
		color: #94a3b8 !important;
		opacity: 1 !important;
	}
	
	input[style*="background: rgba(255, 255, 255, 0.1)"]:-moz-placeholder,
	input[style*="background:rgba(255, 255, 255, 0.1)"]:-moz-placeholder {
		color: #94a3b8 !important;
		opacity: 1 !important;
	}
	
	/* Dark input placeholder class */
	.dark-input-placeholder::placeholder {
		color: #94a3b8 !important;
		opacity: 1 !important;
	}
	
	.dark-input-placeholder::-webkit-input-placeholder {
		color: #94a3b8 !important;
		opacity: 1 !important;
	}
	
	.dark-input-placeholder::-moz-placeholder {
		color: #94a3b8 !important;
		opacity: 1 !important;
	}
	
	.dark-input-placeholder:-ms-input-placeholder {
		color: #94a3b8 !important;
		opacity: 1 !important;
	}
	
	.dark-input-placeholder:-moz-placeholder {
		color: #94a3b8 !important;
		opacity: 1 !important;
	}

	/* ─── ANASAYFA MODERN TEMA (admin paleti: zeytin-yeşili #b8b62e) ─── */
	.section-heading {
		color: #0f172a !important;
		font-weight: 900 !important;
		position: relative;
		display: inline-block;
	}
	.section-heading::after {
		content: "";
		display: block;
		width: 56px;
		height: 4px;
		background: #b8b62e;
		border-radius: 999px;
		margin: 14px auto 0;
	}
	.section-heading.text-white { color: #1a1a1a !important; }
	.section-subheading {
		color: #64748b;
		font-size: 15px;
		max-width: 640px;
		margin: 12px auto 0;
		line-height: 1.7;
	}
	.section-subheading.text-white { color: #777 !important; }

</style>
@endpush

@push('scripts')
@endpush