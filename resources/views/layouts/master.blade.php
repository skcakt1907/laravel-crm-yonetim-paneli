<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <!-- Tüm relative linkler mevcut origin (host + port) üzerinde kalsın diye sadece kök path kullanıyoruz -->
    <base href="/">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @php
        /** @var \stdClass|null $ayarlar */
        $ayarlar = DB::table('ayarlar')->first();
        $siteTitle = $ayarlar->site_baslik ?? 'DN Kreatif İş Ortağım - Web Hosting, Domain, Web Paketleri';
        $metaDescriptionDefault = 'DN Kreatif İş Ortağım platformu ile web hosting, domain tescil, web tasarım paketleri ve dijital hizmetleri kolayca yönetin. 7/24 destek.';
    @endphp
    
    <!-- SEO Meta Tags -->
    <title>@yield('title', $siteTitle)</title>
    <meta name="description" content="@yield('description', $metaDescriptionDefault)" />
    <meta name="keywords" content="@yield('keywords', 'web hosting, domain tescil, web tasarım, dijital ajans, ssl sertifikası, e-ticaret, kurumsal web sitesi')" />
    <meta name="author" content="DN Kreatif Dijital Reklam Ajansı">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', $siteTitle)">
    <meta property="og:description" content="@yield('description', 'Web hosting, domain tescil ve dijital hizmetler için İş Ortağım platformu.')">
    @php
        // Logo ve favicon yolları
        $logoFile = 'site-logo.png';
        $logo_path = asset('tema/uploads/logo/'.$logoFile);
        // Favicon: "io" logosu (admin paneliyle aynı). Ayarlar'dan favicon
        // yönetmek istersen alttaki satırı geri aç, bu satırı kaldır:
        // $favicon_path = $ayarlar && !empty($ayarlar->favicon) ? asset('tema/uploads/logo/'.$ayarlar->favicon) : $logo_path;
        $favicon_path = asset('tema/uploads/favicon/favicon_io.png');
        // Sosyal paylaşım görseli
        $meta_image = $logo_path;
    @endphp
    <meta property="og:image" content="{{ $meta_image }}">
    <meta property="og:locale" content="{{ app()->getLocale() == 'tr' ? 'tr_TR' : 'en_US' }}">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="{{ url()->current() }}">
    <meta name="twitter:title" content="@yield('title', $siteTitle)">
    <meta name="twitter:description" content="@yield('description', 'Web hosting, domain tescil ve dijital hizmetler.')">
    <meta name="twitter:image" content="{{ $meta_image }}">
    
    <!-- Google Translate - Script master-layout.js içinde -->
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('tema/uploads/favicon/favicon_io.svg') }}">
    <link rel="shortcut icon" href="{{ $favicon_path }}" type="image/x-icon">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ $favicon_path }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ $favicon_path }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ $favicon_path }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ $favicon_path }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ $favicon_path }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ $favicon_path }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ $favicon_path }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ $favicon_path }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ $favicon_path }}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{ $favicon_path }}">
    <link rel="apple-touch-icon" sizes="57x57" href="{{ $favicon_path }}">
    <link rel="apple-touch-icon" href="{{ $favicon_path }}">
    <meta name="msapplication-TileColor" content="#b8b62e">
    <meta name="msapplication-TileImage" content="{{ $favicon_path }}">
    <meta name="theme-color" content="#b8b62e">
    
    <!-- Fonts -->
    <link href="{{ asset('tema/fonts/cloudicon/cloudicon.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/fonts/fontawesome/css/all.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/font-awesome.min.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/fonts/opensans/opensans.css') }}" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Fira+Sans:100,200,300,400,500,600,700,800,900" rel="stylesheet">
    <!-- Material Design Icons (for mdi mdi-link usage if needed) -->
    <link rel="stylesheet" href="{{ asset('yonetim/vendors/iconfonts/mdi/font/css/materialdesignicons.min.css') }}">
    
    <!-- CSS styles -->
    <link href="{{ asset('tema/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/idangerous.swiper.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/animate.min.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/slick.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/filter.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/sweetalert2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/mixitup.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/update.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/remodal.css') }}" rel="stylesheet">
    <link href="{{ asset('tema/css/remodal-default-theme.css') }}" rel="stylesheet">
    
    <script src="{{ asset('tema/js/jquery.min.js') }}"></script>
    <script src="{{ asset('tema/js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('tema/js/sweetalert2.min.js') }}"></script>
    
    <!-- Master Layout CSS -->
    <link rel="stylesheet" href="{{ asset('tema/css/master-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('tema/css/inline-styles.css') }}">
    
    <!-- Modern Theme CSS -->
    <link rel="stylesheet" href="{{ asset('tema/css/modern-theme.css') }}">
    
    <!-- Mobile Bottom Navigation CSS -->
    <link rel="stylesheet" href="{{ asset('tema/css/mobile-bottom-nav.css') }}">
    
    <!-- Mobile Navbar Hide Menu CSS -->
    <link rel="stylesheet" href="{{ asset('tema/css/mobile-navbar-hide-menu.css') }}">
    
    <!-- Responsive / Mobile CSS -->
    <link rel="stylesheet" href="{{ asset('tema/css/responsive.css') }}">

    <!-- Yeni Tema Navbar CSS -->
    <link rel="stylesheet" href="{{ asset('tema/css/yeni-tema.css') }}">

    @stack('styles')
    <link rel="stylesheet" href="{{ asset('tema/css/site-polish.css') }}?v={{ filemtime(public_path('tema/css/site-polish.css')) }}">

    <!-- White Theme Override (en son yuklenmeli) -->
    <link rel="stylesheet" href="{{ asset('tema/css/white-theme.css') }}?v={{ time() }}">

    <!-- Tüm public buton'lar sarı (en son yüklenmeli) -->
    <link rel="stylesheet" href="{{ asset('tema/css/buttons-yellow.css') }}?v={{ filemtime(public_path('tema/css/buttons-yellow.css')) }}">

    <!-- Sayfa bazlı override'lar en son yüklenir (white-theme'i ezer) -->
    @yield('page_override')

    <!-- Admin zeytin-yeşili header uyumu (tüm sarı/mor tonları ezer) -->
    <style>
      :root { --io-brand:#b8b62e; --io-brand-hover:#a3a128; --io-brand-soft:rgba(184,182,46,.12); --io-ink:#1a1a0e; }
      /* Sarı butonlar (Kayıt Ol / Hesabım) -> zeytin-yeşili, koyu yazı */
      .yt-btn-yellow {
        background: var(--io-brand) !important;
        color: var(--io-ink) !important;
        border: none !important;
        box-shadow: 0 4px 14px rgba(184,182,46,.30) !important;
        transition: background .2s ease, transform .2s ease !important;
      }
      .yt-btn-yellow:hover { background: var(--io-brand-hover) !important; color: var(--io-ink) !important; transform: translateY(-1px); }
      .yt-btn-yellow i { color: var(--io-ink) !important; }
      /* Aktif menü linki */
      .yt-nav a.active, .yt-nav a:hover { color: var(--io-brand) !important; }
      .yt-nav a.active { background: var(--io-brand-soft) !important; }
      /* Ghost butonlar hover */
      .yt-btn-ghost:hover { color: var(--io-brand) !important; border-color: var(--io-brand) !important; }
      /* Dropdown menü vurgusu */
      .yt-nav-dropdown a:hover { color: var(--io-brand) !important; background: var(--io-brand-soft) !important; }
      /* Mobil menü aktif */
      .mobile-menu-item.active, .mobile-menu-item:hover { color: var(--io-brand) !important; background: var(--io-brand-soft) !important; }
      /* ── HAMBURGER MENÜ SEPET/HESABIM BUTONLARI ──
         Bu butonlar mobil menüde .yt-mobile-menu a'dan 22px font + alt çizgi
         miras alıyordu → iri, çıplak, "kaba" görünüyordu. Şık, dolu buton hâline
         getiriyoruz (uygun font, tam genişlik, yumuşak köşe, ikon hizalı). */
      .yt-mobile-actions { gap: 12px !important; }
      .yt-mobile-actions .yt-btn-ghost,
      .yt-mobile-actions .yt-btn-yellow {
        display: flex !important;
        align-items: center;
        justify-content: center;
        gap: 9px;
        width: 100%;
        font-size: 15.5px !important;
        font-weight: 700 !important;
        font-family: inherit !important;
        padding: 15px 18px !important;
        border-radius: 12px !important;
        border-bottom: 0 !important;
        line-height: 1 !important;
      }
      .yt-mobile-actions .yt-btn-ghost i,
      .yt-mobile-actions .yt-btn-yellow i { font-size: 18px !important; }
      /* Sepet (ghost): görünür ama zarif çerçeve + hafif zemin */
      .yt-mobile-actions .yt-btn-ghost {
        border: 1.5px solid #e5e7eb !important;
        background: #f8f8f4 !important;
        color: #1a1a0e !important;
      }
      .yt-mobile-actions .yt-btn-ghost:hover {
        border-color: var(--io-brand) !important;
        background: var(--io-brand-soft) !important;
        color: var(--io-ink) !important;
      }
      /* Hesabım (yellow): dolgun marka butonu */
      .yt-mobile-actions .yt-btn-yellow {
        box-shadow: 0 6px 18px rgba(184,182,46,.28) !important;
      }
      /* Header alt çizgi/aksan (varsa) */
      .yt-header { border-bottom: 1px solid rgba(15,23,42,.06); }
      /* LOGO KORUMASI: paketler-fix.css'teki aşırı geniş "[style*='height: 100%'] img
         {height:100%!important}" kuralı header logosunu da yakalayıp doğal boyutuna
         (225px) şişiriyordu. #yt-header ID önceliğiyle logoyu sabit boyda tutuyoruz. */
      #yt-header .yt-logo img {
          height: 40px !important;
          max-height: 40px !important;
          width: auto !important;
          object-fit: contain !important;
      }
      @media (max-width: 991px){
          #yt-header .yt-logo img { height: 36px !important; max-height: 36px !important; }
      }
      /* Paketler sayfası arama input placeholder - en güçlü override */
      #paket-arama-kelime { color: #1f2937 !important; -webkit-text-fill-color: #1f2937 !important; background: #f9fafb !important; }
      #paket-arama-kelime::placeholder { color: #64748b !important; -webkit-text-fill-color: #64748b !important; opacity: 1 !important; }
      #paket-arama-kelime::-webkit-input-placeholder { color: #64748b !important; -webkit-text-fill-color: #64748b !important; opacity: 1 !important; }
      #paket-arama-kelime::-moz-placeholder { color: #64748b !important; opacity: 1 !important; }
      #paket-arama-kelime:-ms-input-placeholder { color: #64748b !important; opacity: 1 !important; }
      #paket-arama-kelime:-webkit-autofill,
      #paket-arama-kelime:-webkit-autofill:hover,
      #paket-arama-kelime:-webkit-autofill:focus {
        -webkit-text-fill-color: #1f2937 !important;
        -webkit-box-shadow: 0 0 0px 1000px #f9fafb inset !important;
      }
    </style>
</head>
<body>
    @php
        $isUyeLoggedIn = Auth::guard('uye')->check();
        $isBayiAdminLoggedIn = session('admin_logged_in') && (int) session('admin_rol') === 3;
        // Hesabım her zaman müşteri paneline (hesabim) yönlendirir.
        // Bayi paneline geçiş, müşteri panelinin içindeki "Bayilik Paneli" butonu ile yapılır.
        // Sadece üye oturumu YOKSA ve bayi admin oturumu VARSA direkt bayi paneline git.
        $accountUrl = $isUyeLoggedIn
            ? localized_route('hesabim')
            : ($isBayiAdminLoggedIn ? route('admin.bayi.dashboard') : localized_route('hesabim'));
        // Buton yazısı: yalnızca uye oturumu yokken bayi admin oturumu varsa "Bayi Paneli", aksi halde "Hesabım"
        $accountLabel = (!$isUyeLoggedIn && $isBayiAdminLoggedIn) ? 'Bayi Paneli' : 'Hesabım';
        // Frontend tarafında çıkış her zaman ortak çıkış endpoint'inden yapılmalı.
        $logoutUrl = localized_route('cikis');
    @endphp

    <!-- LOADING PAGE -->
    <div id="spinner-area" style="display:none;">
        <div class="spinner">
            <div class="double-bounce1"></div>
            <div class="double-bounce2"></div>
            <div class="spinner-txt">{{ __('messages.loading') }}</div>
        </div>
    </div>

    <!-- NAVBAR -->
    <header class="yt-header" id="yt-header">
        @php
            $current_currency = request('currency') ?? session('currency', 'TRY');
            $currency_symbol = match($current_currency) { 'USD'=>'$','EUR'=>'€','AED'=>'د.إ',default=>'₺' };
            $current_lang = app()->getLocale();
            $lang_icon = match($current_lang) { 'tr'=>'🇹🇷','en'=>'🇬🇧','ar'=>'🇸🇦',default=>'🌐' };
            // Footer ile ayni kaynak/yedek degerler kullanilir
            $ust_tel  = $ayarlar->firma_telefon ?? '0 (850) 307 95 48';
            $ust_mail = $ayarlar->firma_email ?? 'isortagim@ornek.com';
        @endphp

        {{-- Üst şerit kaldırıldı; dil + para birimi navbar aksiyonlarına geri taşındı. --}}

        <div class="yt-container">
            <nav class="yt-nav-inner">

                <!-- Logo -->
                <a href="{{ localized_route('anasayfa') }}" class="yt-logo">
                    <img src="{{ $logo_path }}" alt="{{ $ayarlar->firma_adi ?? config('app.name') }}" style="height:40px;width:auto;object-fit:contain;" >
                </a>

                <!-- Desktop Nav Links -->
                <div class="yt-nav">
                    @if(isset($menuler) && count($menuler) > 0)
                        @foreach($menuler as $menu)
                            @php
                                $href = $menu->menu_url == '0' ? $menu->link : $menu->menu_url;
                                if (!str_starts_with($href, 'http') && !str_starts_with($href, 'javascript')) {
                                    $href = localized_url($href);
                                }
                                $currentPath = ltrim(request()->path(), '/');
                                $menuPath = ltrim(parse_url($href, PHP_URL_PATH) ?? '', '/');
                                $isActive = $menuPath && ($currentPath === $menuPath || str_starts_with($currentPath, $menuPath . '/'));
                            @endphp
                            @if(isset($menu->altmenu) && count($menu->altmenu) > 0)
                                <div class="yt-nav-dropdown">
                                    <a href="{{ $href }}" class="{{ $isActive ? 'active' : '' }}" @if($menu->sekme == 1) target="_blank" @endif>
                                        {{ $menu->menu_isim }} <i class="mdi mdi-chevron-down" style="font-size:12px;"></i>
                                    </a>
                                    <div class="yt-dropdown-menu">
                                        @foreach($menu->altmenu as $altmenu)
                                            @php
                                                $alt_href = $altmenu->menu_url == '0' ? $altmenu->link : $altmenu->menu_url;
                                                if (!str_starts_with($alt_href, 'http') && !str_starts_with($alt_href, 'javascript')) {
                                                    $alt_href = localized_url($alt_href);
                                                }
                                            @endphp
                                            <a href="{{ $alt_href }}" @if($altmenu->sekme == 1) target="_blank" @endif>{{ $altmenu->menu_isim }}</a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <a href="{{ $href }}" class="{{ $isActive ? 'active' : '' }}" @if($menu->sekme == 1) target="_blank" @endif>{{ $menu->menu_isim }}</a>
                            @endif
                        @endforeach
                    @else
                        <a href="{{ localized_route('anasayfa') }}" class="{{ request()->routeIs('anasayfa') ? 'active' : '' }}">{{ __('messages.home') }}</a>
                        <a href="{{ localized_route('paketler') }}" class="{{ request()->routeIs('paketler','paket.detay','paketler.kategori') ? 'active' : '' }}">{{ __('messages.packages') }}</a>

                        {{-- Hizmetler dropdown (navbar taşmasın diye gruplandı) --}}
                        @php $hizmetAktif = request()->routeIs('domain.*','hosting','hosting.detay'); @endphp
                        <div class="yt-nav-dropdown">
                            <a href="javascript:void(0)" class="{{ $hizmetAktif ? 'active' : '' }}">Hizmetler <i class="mdi mdi-chevron-down" style="font-size:12px;"></i></a>
                            <div class="yt-dropdown-menu">
                                <a href="{{ localized_route('domain.tescil') }}">{{ __('messages.domain') }}</a>
                                <a href="{{ localized_route('hosting') }}">{{ __('messages.web_hosting') }}</a>
                            </div>
                        </div>

                        <a href="{{ localized_route('firsatlar') }}" class="{{ request()->routeIs('firsatlar') ? 'active' : '' }}">{{ __('messages.opportunities') }}</a>

                        {{-- Kurumsal dropdown (referanslar + blog + anketler burada) --}}
                        @php $kurumsalAktif = request()->routeIs('blog','blog.*','referanslar','referans.detay','arge.anketi','is.basvurusu'); @endphp
                        <div class="yt-nav-dropdown">
                            <a href="javascript:void(0)" class="{{ $kurumsalAktif ? 'active' : '' }}">Kurumsal <i class="mdi mdi-chevron-down" style="font-size:12px;"></i></a>
                            <div class="yt-dropdown-menu">
                                @if(Route::has('referanslar'))<a href="{{ localized_route('referanslar') }}">Referanslarımız</a>@endif
                                <a href="{{ localized_route('blog') }}">{{ __('messages.blog') }}</a>
                                @if(Route::has('arge.anketi'))<a href="{{ localized_route('arge.anketi') }}">{{ __('messages.arge_anketi') }}</a>@endif
                                @if(Route::has('is.basvurusu'))<a href="{{ localized_route('is.basvurusu') }}">{{ __('messages.is_basvurusu') }}</a>@endif
                            </div>
                        </div>

                        <a href="{{ localized_route('iletisim') }}" class="{{ request()->routeIs('iletisim') ? 'active' : '' }}">{{ __('messages.contact') }}</a>
                    @endif
                </div>

                <!-- Actions -->
                <div class="yt-header-actions">
                    {{-- Para Birimi (navbar'a geri taşındı) --}}
                    <div class="yt-nav-dropdown">
                        <a href="javascript:void(0)" class="yt-btn-ghost yt-util-btn">{{ $currency_symbol }} {{ $current_currency }}</a>
                        <div class="yt-dropdown-menu yt-dropdown-right">
                            <a href="{{ request()->fullUrlWithQuery(['currency' => 'TRY', 'lang' => $current_lang]) }}">₺ TRY</a>
                            <a href="{{ request()->fullUrlWithQuery(['currency' => 'USD', 'lang' => $current_lang]) }}">$ USD</a>
                            <a href="{{ request()->fullUrlWithQuery(['currency' => 'EUR', 'lang' => $current_lang]) }}">€ EUR</a>
                            <a href="{{ request()->fullUrlWithQuery(['currency' => 'AED', 'lang' => $current_lang]) }}">د.إ AED</a>
                        </div>
                    </div>
                    {{-- Dil (navbar'a geri taşındı) --}}
                    <div class="yt-nav-dropdown">
                        <a href="javascript:void(0)" class="yt-btn-ghost yt-util-btn">{{ $lang_icon }}</a>
                        <div class="yt-dropdown-menu yt-dropdown-right">
                            <a href="{{ request()->fullUrlWithQuery(['lang' => 'tr']) }}">🇹🇷 Türkçe</a>
                            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}">🇬🇧 English</a>
                            <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}">🇸🇦 العربية</a>
                        </div>
                    </div>

                    @if($isUyeLoggedIn || $isBayiAdminLoggedIn)
                        {{-- 🔔 Bildirim zili --}}
                        @if(Route::has('bildirimlerim'))
                        <div class="yt-nav-dropdown yt-bell-wrap" id="uyeBellWrap">
                            <a href="javascript:void(0)" class="yt-btn-ghost yt-bell-btn" onclick="uyeBellToggle(event)" style="position:relative">
                                <i class="mdi mdi-bell"></i>
                                <span id="uyeBellBadge" class="yt-bell-badge" style="display:none"></span>
                            </a>
                            <div class="yt-dropdown-menu yt-dropdown-right yt-bell-menu" id="uyeBellMenu" style="min-width:320px;max-width:360px;padding:0">
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-bottom:1px solid #eee">
                                    <strong style="font-size:14px">Bildirimler</strong>
                                    <a href="{{ route('bildirimlerim') }}" style="font-size:12px;color:var(--io-brand,#8a8a1f);text-decoration:none">Tümü</a>
                                </div>
                                <div id="uyeBellList" style="max-height:360px;overflow-y:auto">
                                    <div style="padding:20px;text-align:center;color:#999;font-size:13px">{{ __('messages.loading') }}</div>
                                </div>
                            </div>
                        </div>
                        <style>
                            .yt-bell-badge{position:absolute;top:-2px;right:-2px;min-width:16px;height:16px;padding:0 4px;border-radius:9px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;display:flex;align-items:center;justify-content:center;line-height:1}
                            .yt-bell-item{display:block;padding:11px 16px;border-bottom:1px solid #f1f1f1;text-decoration:none;color:#1f2419;transition:background .12s}
                            .yt-bell-item:hover{background:#f7f8f3}
                            .yt-bell-item.unread{background:rgba(184,182,46,.07)}
                            .yt-bell-item .b-title{font-weight:700;font-size:13px;margin-bottom:2px}
                            .yt-bell-item .b-msg{font-size:12px;color:#666;line-height:1.4}
                            .yt-bell-item .b-time{font-size:10.5px;color:#aaa;margin-top:3px}
                        </style>
                        @endif

                        <a href="{{ localized_route('sepet') }}" class="yt-btn-ghost">
                            <i class="mdi mdi-basket"></i> {{ $sepet_sayisi ?? 0 }}
                        </a>
                        @php
                            $isBayiUye = $isUyeLoggedIn && Auth::guard('uye')->user() && (Auth::guard('uye')->user()->bayi ?? 0) > 0;
                        @endphp
                        <div class="yt-nav-dropdown yt-account-dropdown">
                            <a href="javascript:void(0)" class="yt-btn-yellow">
                                <i class="mdi mdi-account-circle"></i> {{ $accountLabel === 'Bayi Paneli' ? 'Bayi Paneli' : __('messages.my_account') }}
                                <i class="mdi mdi-chevron-down" style="font-size:14px;margin-left:2px;"></i>
                            </a>
                            <div class="yt-dropdown-menu yt-dropdown-right">
                                <a href="{{ localized_route('hesabim') }}"><i class="mdi mdi-view-dashboard"></i> {{ __('messages.my_account') }}</a>
                                <a href="{{ route('bilgilerim') }}"><i class="mdi mdi-account-edit"></i> Bilgilerim</a>
                                <a href="{{ route('faturalarim') }}"><i class="mdi mdi-file-document"></i> {{ __('messages.panel_invoices') }}</a>
                                @if(Route::has('randevularim'))
                                <a href="{{ route('randevularim') }}"><i class="mdi mdi-calendar-clock"></i> {{ __('messages.my_appointments') }}</a>
                                @endif
                                @if(Route::has('gorevlerim'))
                                <a href="{{ route('gorevlerim') }}"><i class="mdi mdi-format-list-checks"></i> {{ __('messages.my_tasks') }}</a>
                                @endif
                                <a href="{{ localized_route('sepet') }}"><i class="mdi mdi-basket"></i> Sepetim</a>
                                @if($isBayiUye && Route::has('admin.bayi.dashboard'))
                                    <div style="border-top:1px solid #eee;margin:6px 0"></div>
                                    <a href="{{ route('admin.bayi.dashboard') }}" style="background:#fef3c7;color:#92400e;font-weight:700">
                                        <i class="mdi mdi-handshake"></i> {{ __('messages.switch_to_reseller_panel') }}
                                    </a>
                                @elseif($isBayiAdminLoggedIn)
                                    <div style="border-top:1px solid #eee;margin:6px 0"></div>
                                    <a href="{{ route('admin.bayi.dashboard') }}" style="background:#fef3c7;color:#92400e;font-weight:700">
                                        <i class="mdi mdi-handshake"></i> Bayi Paneli
                                    </a>
                                @endif
                                <div style="border-top:1px solid #eee;margin:6px 0"></div>
                                <a href="{{ $logoutUrl }}" style="color:#dc2626">
                                    <i class="mdi mdi-logout"></i> {{ __('messages.logout') }}
                                </a>
                            </div>
                        </div>
                    @else
                        <a href="{{ localized_route('giris') }}" class="yt-btn-ghost">
                            <i class="mdi mdi-login"></i> {{ __('messages.login') }}
                        </a>
                        <a href="{{ localized_route('kayit') }}" class="yt-btn-yellow">
                            <i class="mdi mdi-account-plus"></i> {{ __('messages.register') }}
                        </a>
                    @endif

                    <!-- Mobil Hamburger -->
                    <button class="yt-hamburger" id="yt-hamburger" aria-label="{{ __('messages.open_menu') }}">
                        <span></span><span></span><span></span>
                    </button>
                </div>

            </nav>
        </div>
    </header>

    {{-- Header'a üst şerit eklenince fixed header 68px'ten 106px'e çıktı. Sayfaların
         kendi üst boşlukları 68px'e göre ayarlı; aradaki 38px'i burada telafi ediyoruz
         -> hiçbir sayfanın padding'ine dokunmaya gerek kalmıyor. --}}
    <div class="yt-header-spacer" aria-hidden="true"></div>

    <!-- Mobil Menü -->
    <div class="yt-mobile-menu" id="yt-mobile-menu">
        <button class="yt-mobile-close" id="yt-mobile-close" aria-label="{{ __('messages.close') }}">&#10005;</button>
        @if(isset($menuler) && count($menuler) > 0)
            @foreach($menuler as $menu)
                @php
                    $href = $menu->menu_url == '0' ? $menu->link : $menu->menu_url;
                    if (!str_starts_with($href, 'http') && !str_starts_with($href, 'javascript')) {
                        $href = localized_url($href);
                    }
                @endphp
                <a href="{{ $href }}" @if($menu->sekme == 1) target="_blank" @endif>{{ $menu->menu_isim }}</a>
            @endforeach
        @else
            <a href="{{ localized_route('anasayfa') }}">{{ __('messages.home') }}</a>
            <a href="{{ localized_route('paketler') }}">{{ __('messages.packages') }}</a>
            <a href="{{ localized_route('domain.tescil') }}">{{ __('messages.domain') }}</a>
            <a href="{{ localized_route('hosting') }}">{{ __('messages.web_hosting') }}</a>
            <a href="{{ localized_route('firsatlar') }}">{{ __('messages.opportunities') }}</a>
            <a href="{{ localized_route('blog') }}">{{ __('messages.blog') }}</a>
            <a href="{{ localized_route('iletisim') }}">{{ __('messages.contact') }}</a>
        @endif
        {{-- Ar-Ge Anketi + İş Başvurusu (Rubito) — masaüstü navbar ile tutarlı --}}
        @if(Route::has('arge.anketi'))
            <a href="{{ localized_route('arge.anketi') }}" class="{{ request()->routeIs('arge.anketi') ? 'active' : '' }}">{{ __('messages.arge_anketi') }}</a>
        @endif
        @if(Route::has('is.basvurusu'))
            <a href="{{ localized_route('is.basvurusu') }}" class="{{ request()->routeIs('is.basvurusu') ? 'active' : '' }}">{{ __('messages.is_basvurusu') }}</a>
        @endif
        <div class="yt-mobile-actions" style="margin-top:24px;">
            @if($isUyeLoggedIn || $isBayiAdminLoggedIn)
                <a href="{{ localized_route('sepet') }}" class="yt-btn-ghost" style="justify-content:center;">
                    <i class="mdi mdi-basket"></i> Sepet ({{ $sepet_sayisi ?? 0 }})
                </a>
                <a href="{{ $accountUrl }}" class="yt-btn-yellow" style="justify-content:center;">
                    <i class="mdi mdi-lock"></i> {{ $accountLabel }}
                </a>
            @else
                <a href="{{ localized_route('giris') }}" class="yt-btn-ghost" style="justify-content:center;">
                    <i class="mdi mdi-login"></i> {{ __('messages.login') }}
                </a>
                <a href="{{ localized_route('kayit') }}" class="yt-btn-yellow" style="justify-content:center;">
                    <i class="mdi mdi-account-plus"></i> {{ __('messages.register') }}
                </a>
            @endif
        </div>
    </div>

    @include('_partials.flash-toast')

    <!-- CONTENT -->
    @yield('content')

    <!-- MOBILE BOTTOM NAVIGATION BAR — kaldırıldı (gizlendi) -->
    <div class="mobile-bottom-nav" style="display:none !important">
        <div class="mobile-bottom-nav-scroll">
            <div class="mobile-bottom-nav-items">
                <!-- Anasayfa -->
                <a href="{{ localized_route('anasayfa') }}" class="mobile-bottom-nav-item @if(request()->routeIs('anasayfa')) active @endif">
                    <i class="mdi mdi-home"></i>
                    <span>ANASAYFA</span>
                </a>
                
                <!-- Paketler -->
                <a href="{{ localized_route('paketler') }}" class="mobile-bottom-nav-item @if(request()->routeIs('paketler') || request()->routeIs('paket.detay') || request()->routeIs('paketler.kategori')) active @endif">
                    <i class="mdi mdi-package-variant"></i>
                    <span>PAKETLER</span>
                </a>
                
                <!-- Ortadaki slot: Giriş yapılmışsa Hesabım, değilse Domain -->
                @if($isUyeLoggedIn || $isBayiAdminLoggedIn)
                    <a href="{{ $accountUrl }}" class="mobile-bottom-nav-item @if(request()->routeIs('hesabim') || request()->routeIs('admin.bayi.*')) active @endif">
                        <i class="mdi mdi-account-circle"></i>
                        <span>{{ $isBayiAdminLoggedIn ? 'BAYİ PANELİ' : 'HESABIM' }}</span>
                    </a>
                @else
                    <a href="{{ localized_route('domain.tescil') }}" class="mobile-bottom-nav-item @if(request()->routeIs('domain.tescil') || request()->routeIs('domain.sorgula')) active @endif">
                        <i class="mdi mdi-earth"></i>
                        <span>DOMAIN</span>
                    </a>
                @endif
                
                <!-- Web Hosting -->
                <a href="{{ localized_route('hosting') }}" class="mobile-bottom-nav-item @if(request()->routeIs('hosting') || request()->routeIs('hosting.detay')) active @endif">
                    <i class="mdi mdi-server"></i>
                    <span>WEB HOSTING</span>
                </a>
                
                <!-- Daha Fazla -->
                <button type="button" id="mobileBottomMore" class="mobile-bottom-nav-item mobile-bottom-nav-more">
                    <i class="mdi mdi-menu"></i>
                    <span>DAHA FAZLA</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Mobile Slide Menu (Daha Fazla için) -->
    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>
    <div class="mobile-menu" id="mobileMenu">
        @php
            // Mevcut para birimi ve dili al (mobil slide menüde aktif vurgusu için)
            $currentCurrencyRaw = request('currency') ?? session('currency', 'TRY');
            $currentCurrency = strtoupper($currentCurrencyRaw);
            // TL / TRY normalize et (linklerde TRY kullanıyoruz)
            if ($currentCurrency === 'TL') {
                $currentCurrency = 'TRY';
            }
            $currentLang = app()->getLocale();

            $activeItemStyle = 'background: rgba(184, 182, 46, 0.10); color: #b8b62e; border-left: 3px solid #b8b62e;';
        @endphp
        <div class="mobile-menu-header">
            <a href="{{ localized_route('anasayfa') }}" class="mobile-menu-logo-link">
                <img src="{{ $logo_path }}" alt="{{ $ayarlar->firma_adi ?? config('app.name') }}" class="mobile-menu-logo">
            </a>
            <button type="button" class="mobile-menu-close" id="mobileMenuClose">
                <i class="mdi mdi-close"></i>
            </button>
        </div>
        
        <div class="mobile-menu-list">
            <div class="mobile-menu-section-title">{{ __('messages.panel_menu') }}</div>
            
            @if(isset($menuler) && count($menuler) > 0)
                @foreach($menuler->where('ust_menu', 0) as $menu)
                    @php
                        $href = $menu->menu_url == '0' ? $menu->link : $menu->menu_url;
                        if (!str_starts_with($href, 'http') && !str_starts_with($href, 'javascript')) {
                            $href = localized_url($href);
                        }
                    @endphp
                    @if(isset($menu->altmenu) && count($menu->altmenu) > 0)
                        <div class="mobile-menu-dropdown">
                            <a href="javascript:void(0)" class="mobile-menu-item mobile-menu-toggle">
                                {{ $menu->menu_isim }} <i class="mdi mdi-chevron-down" style="float: right;"></i>
                            </a>
                            <div class="mobile-submenu">
                                @foreach($menu->altmenu as $altmenu)
                                    @php
                                        $alt_href = $altmenu->menu_url == '0' ? $altmenu->link : $altmenu->menu_url;
                                        if (!str_starts_with($alt_href, 'http') && !str_starts_with($alt_href, 'javascript')) {
                                            $alt_href = localized_url($alt_href);
                                        }
                                    @endphp
                                    <a href="{{ $alt_href }}" class="mobile-menu-item mobile-submenu-item" @if($altmenu->sekme == 1) target="_blank" @endif>
                                        <i class="mdi mdi-chevron-right" style="margin-right: 8px;"></i>{{ $altmenu->menu_isim }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $href }}" class="mobile-menu-item" @if($menu->sekme == 1) target="_blank" @endif>{{ $menu->menu_isim }}</a>
                    @endif
                @endforeach
            @else
                <a href="{{ localized_route('anasayfa') }}" class="mobile-menu-item" style="{{ request()->routeIs('anasayfa') ? $activeItemStyle : '' }}">{{ __('messages.home') }}</a>
                <a href="{{ localized_route('paketler') }}" class="mobile-menu-item" style="{{ request()->routeIs('paketler') || request()->routeIs('paket.detay') || request()->routeIs('paketler.kategori') ? $activeItemStyle : '' }}">{{ __('messages.packages') }}</a>
                <a href="{{ localized_route('domain.tescil') }}" class="mobile-menu-item" style="{{ request()->routeIs('domain.tescil') || request()->routeIs('domain.sorgula') ? $activeItemStyle : '' }}">{{ __('messages.domain') }}</a>
                <a href="{{ localized_route('hosting') }}" class="mobile-menu-item" style="{{ request()->routeIs('hosting') || request()->routeIs('hosting.detay') ? $activeItemStyle : '' }}">Web Hosting</a>
                <a href="{{ localized_route('firsatlar') }}" class="mobile-menu-item" style="{{ request()->routeIs('firsatlar') ? $activeItemStyle : '' }}">{{ __('messages.opportunities_title') }}</a>
                <a href="{{ localized_route('blog') }}" class="mobile-menu-item" style="{{ request()->routeIs('blog') || request()->routeIs('blog.detay') || request()->routeIs('blog.kategori') ? $activeItemStyle : '' }}">Blog</a>
                @if(Route::has('referanslar'))<a href="{{ localized_route('referanslar') }}" class="mobile-menu-item" style="{{ request()->routeIs('referanslar') || request()->routeIs('referans.detay') ? $activeItemStyle : '' }}">Referanslarımız</a>@endif
                <a href="{{ localized_route('iletisim') }}" class="mobile-menu-item" style="{{ request()->routeIs('iletisim') ? $activeItemStyle : '' }}">{{ __('messages.contact_title') }}</a>
            @endif
            
            <div class="mobile-menu-section-title">{{ __('messages.currency_label') }}</div>
            {{-- Mobilde para birimi: aktif olan sarı renkle vurgulanır, JS mevcut sayfanın URL'ine currency parametresi ekler --}}
            <a href="?currency=TRY" class="mobile-menu-item mobile-currency-link" data-currency="TRY" style="{{ $currentCurrency === 'TRY' ? $activeItemStyle : '' }}">{{ __('messages.currency_try') }}</a>
            <a href="?currency=USD" class="mobile-menu-item mobile-currency-link" data-currency="USD" style="{{ $currentCurrency === 'USD' ? $activeItemStyle : '' }}">$ USD - US Dollar</a>
            <a href="?currency=EUR" class="mobile-menu-item mobile-currency-link" data-currency="EUR" style="{{ $currentCurrency === 'EUR' ? $activeItemStyle : '' }}">€ EUR - Euro</a>
            <a href="?currency=AED" class="mobile-menu-item mobile-currency-link" data-currency="AED" style="{{ $currentCurrency === 'AED' ? $activeItemStyle : '' }}">د.إ AED - Dirham</a>
            
            <div class="mobile-menu-section-title">{{ __('messages.language_label') }}</div>
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'tr']) }}" class="mobile-menu-item" style="{{ $currentLang === 'tr' ? $activeItemStyle : '' }}">🇹🇷 Türkçe</a>
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="mobile-menu-item" style="{{ $currentLang === 'en' ? $activeItemStyle : '' }}">🇬🇧 English</a>
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'ar']) }}" class="mobile-menu-item" style="{{ $currentLang === 'ar' ? $activeItemStyle : '' }}">🇸🇦 العربية</a>
            
            @if($isUyeLoggedIn || $isBayiAdminLoggedIn)
                <div class="mobile-menu-section-title">HESABIM</div>
                <a href="{{ $accountUrl }}" class="mobile-menu-item">
                    <i class="mdi mdi-account" style="margin-right: 8px;"></i> {{ $accountLabel }}
                </a>
                <a href="{{ localized_route('sepet') }}" class="mobile-menu-item">
                    <i class="mdi mdi-basket" style="margin-right: 8px;"></i> Sepetim
                </a>
                <a href="{{ $logoutUrl }}" class="mobile-menu-item" style="color: #ef4444;">
                    <i class="mdi mdi-power" style="margin-right: 8px;"></i> {{ __('messages.logout') }}
                </a>
            @else
                <div class="mobile-menu-section-title">{{ __('messages.login_label') }}</div>
                <a href="{{ localized_route('giris') }}" class="mobile-menu-item" style="background: rgba(184, 182, 46, 0.10); color: #b8b62e; font-weight: 700;">
                    <i class="mdi mdi-login" style="margin-right: 8px;"></i> {{ __('messages.login_here') }}
                </a>
                <a href="{{ localized_route('kayit') }}" class="mobile-menu-item">
                    <i class="mdi mdi-account-plus" style="margin-right: 8px;"></i> {{ __('messages.register') }}
                </a>
            @endif
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="footer" style="background:#1a2332; color:#fff; padding:60px 0 0; margin-top:0;">
        <div class="container" style="max-width:1200px;">
            {{-- Üst kısım: 4 kolonlu --}}
            <div class="footer-grid" style="display:grid; grid-template-columns: 1fr 1.3fr 1fr 1.4fr; gap:40px;">

                {{-- Kolon 1: Kurumsal --}}
                <div>
                    <h4 style="color:#fff; font-size:17px; font-weight:700; margin:0 0 22px;">Kurumsal</h4>
                    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:12px;">
                        <li><a href="https://ornek.com/bayilik/" target="_blank" rel="noopener" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">Bayilik</a></li>
                        <li><a href="https://ornek.com/hakkimizda/" target="_blank" rel="noopener" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">{{ __('messages.about_us') }}</a></li>
                        <li><a href="https://ornek.com/referanslarimiz/" target="_blank" rel="noopener" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">{{ __('messages.our_references_title') }}</a></li>
                        <li><a href="/sayfa/hesap-numaralarimiz" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">Banka Bilgileri</a></li>
                        <li><a href="/odeme-bildirim-formu" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">{{ __('messages.payment_notification') }}</a></li>
                    </ul>
                </div>

                {{-- Kolon 2: Firma Politikaları --}}
                <div>
                    <h4 style="color:#fff; font-size:17px; font-weight:700; margin:0 0 22px;">{{ __('messages.company_policies') }}</h4>
                    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:12px;">
                        <li><a href="/sayfa/uyelik-sozlesmesi" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">{{ __('messages.membership_agreement_title') }}</a></li>
                        <li><a href="/sayfa/hizmet-ve-kullanim-sozlesmesi" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">{{ __('messages.terms_of_use_agreement') }}</a></li>
                        <li><a href="/sayfa/gizlilik-sozlesmesi" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">{{ __('messages.privacy_agreement') }}</a></li>
                        <li><a href="/sayfa/cerez-politikasi" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">{{ __('messages.cookie_policy') }}</a></li>
                        <li><a href="/sayfa/hosting-domain-sozlesmesi" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">{{ __('messages.hosting_domain_agreement') }}</a></li>
                    </ul>
                </div>

                {{-- Kolon 3: Hizmetler --}}
                <div>
                    <h4 style="color:#fff; font-size:17px; font-weight:700; margin:0 0 22px;">Hizmetler</h4>
                    <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:12px;">
                        <li><a href="{{ localized_route('hosting') }}" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">Web Hosting</a></li>
                        <li><a href="{{ localized_route('domain.tescil') }}" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">{{ __('messages.domain_registration_footer') }}</a></li>
                        <li><a href="/paketler" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">Ajans Hizmetleri</a></li>
                        <li><a href="{{ localized_route('firsatlar') }}" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';">{{ __('messages.opportunities_title') }}</a></li>
                    </ul>
                </div>

                {{-- Kolon 4: İletişim --}}
                <div>
                    <div style="display:flex; gap:10px; align-items:flex-start; margin-bottom:16px;">
                        <i class="mdi mdi-map-marker" style="font-size:18px; color:#b8b62e; margin-top:2px; flex-shrink:0;"></i>
                        <span style="color:#a0a8b8; font-size:14px; line-height:1.6;">{{ $ayarlar->firma_adres ?? 'Fulya Mah. Bahçeler Sok. No: 9/A Kat: 3 D: 7' }}<br>Şişli / İstanbul</span>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center; margin-bottom:12px;">
                        <i class="mdi mdi-phone" style="font-size:17px; color:#b8b62e; flex-shrink:0;"></i>
                        <a href="tel:{{ $ayarlar->firma_telefon ?? '02129930218' }}" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';"><bdi dir="ltr">{{ $ayarlar->firma_telefon ?? '0 (212) 993 02 18' }}</bdi></a>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center; margin-bottom:12px;">
                        <i class="mdi mdi-phone" style="font-size:17px; color:#b8b62e; flex-shrink:0;"></i>
                        <a href="tel:08503079548" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';"><bdi dir="ltr">0 (850) 307 95 48</bdi></a>
                    </div>
                    <div style="display:flex; gap:10px; align-items:center; margin-bottom:18px;">
                        <i class="mdi mdi-email" style="font-size:17px; color:#b8b62e; flex-shrink:0;"></i>
                        <a href="mailto:{{ $ayarlar->firma_email ?? 'isortagim@ornek.com' }}" style="color:#a0a8b8; text-decoration:none; font-size:14px; transition:color .2s ease;" onmouseover="this.style.color='#b8b62e';" onmouseout="this.style.color='#a0a8b8';"><bdi dir="ltr">{{ $ayarlar->firma_email ?? 'isortagim@ornek.com' }}</bdi></a>
                    </div>

                    {{-- Sosyal medya --}}
                    <div style="display:flex; gap:8px;">
                        @if($ayarlar && $ayarlar->facebook)
                        <a href="{{ $ayarlar->facebook }}" target="_blank" rel="noopener" style="width:38px; height:38px; border-radius:50%; background:#3b5998; display:inline-flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; transition:transform .2s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                            <i class="mdi mdi-facebook" style="font-size:18px;"></i>
                        </a>
                        @endif
                        @if($ayarlar && $ayarlar->twitter)
                        <a href="{{ $ayarlar->twitter }}" target="_blank" rel="noopener" style="width:38px; height:38px; border-radius:50%; background:#1da1f2; display:inline-flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; transition:transform .2s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                            <i class="mdi mdi-twitter" style="font-size:18px;"></i>
                        </a>
                        @endif
                        @if($ayarlar && $ayarlar->instagram)
                        <a href="{{ $ayarlar->instagram }}" target="_blank" rel="noopener" style="width:38px; height:38px; border-radius:50%; background:linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%); display:inline-flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; transition:transform .2s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                            <i class="mdi mdi-instagram" style="font-size:18px;"></i>
                        </a>
                        @endif
                        @if($ayarlar && isset($ayarlar->linkedin) && $ayarlar->linkedin)
                        <a href="{{ $ayarlar->linkedin }}" target="_blank" rel="noopener" style="width:38px; height:38px; border-radius:50%; background:#0a66c2; display:inline-flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; transition:transform .2s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                            <i class="mdi mdi-linkedin" style="font-size:18px;"></i>
                        </a>
                        @endif
                        @if($ayarlar && isset($ayarlar->youtube) && $ayarlar->youtube)
                        <a href="{{ $ayarlar->youtube }}" target="_blank" rel="noopener" style="width:38px; height:38px; border-radius:50%; background:#ff0000; display:inline-flex; align-items:center; justify-content:center; color:#fff; text-decoration:none; transition:transform .2s ease;" onmouseover="this.style.transform='translateY(-2px)';" onmouseout="this.style.transform='translateY(0)';">
                            <i class="mdi mdi-youtube" style="font-size:18px;"></i>
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- App Store + Google Play butonları (ortada) --}}
            <div class="dn-store-badges" style="display:flex; justify-content:center; gap:14px; margin:50px 0 30px; flex-wrap:wrap;">
                <a href="https://play.google.com/store/apps/details?id=com.rzmobile.isortagim&hl=tr&gl=US" target="_blank" rel="noopener" class="dn-store-badge" aria-label="Google Play'den indir">
                    <svg viewBox="0 0 135 40" width="150" height="44" xmlns="http://www.w3.org/2000/svg">
                        <rect width="135" height="40" rx="6" fill="#000" stroke="#3a3a3a" stroke-width="0.6"/>
                        <path d="M10.4 8.3c-.27.29-.43.74-.43 1.32v20.76c0 .58.16 1.03.43 1.32l.07.07 11.63-11.63v-.27L10.47 8.23l-.07.07z" fill="#5BC9F4"/>
                        <path d="M26 23.79l-3.87-3.88v-.27L26 15.76l.09.05 4.59 2.61c1.31.74 1.31 1.96 0 2.71l-4.59 2.61-.09.05z" fill="#FFD400"/>
                        <path d="M26.09 23.74L22.13 19.78 10.4 31.51c.43.46 1.15.51 1.96.06l13.73-7.83" fill="#F43249"/>
                        <path d="M26.09 15.81L12.36 7.99c-.81-.46-1.53-.4-1.96.06l11.73 11.73 3.96-3.97z" fill="#00EE76"/>
                        <text x="42" y="16" fill="#fff" font-family="Arial,Helvetica,sans-serif" font-size="7" opacity=".9">{{ __('messages.download_now') }}</text>
                        <text x="42" y="30" fill="#fff" font-family="Arial,Helvetica,sans-serif" font-size="14" font-weight="600">Google Play</text>
                    </svg>
                </a>
                <a href="https://apps.apple.com/us/app/dn-i-%C5%9F-orta%C4%9F%C4%B1m/id1603113206" target="_blank" rel="noopener" class="dn-store-badge" aria-label="App Store'dan indir">
                    <svg viewBox="0 0 135 40" width="150" height="44" xmlns="http://www.w3.org/2000/svg">
                        <rect width="135" height="40" rx="6" fill="#000" stroke="#3a3a3a" stroke-width="0.6"/>
                        <path d="M28.7 20.3c-.02-2.4 1.96-3.56 2.05-3.62-1.12-1.63-2.86-1.86-3.48-1.88-1.48-.15-2.89.87-3.64.87-.75 0-1.91-.85-3.14-.83-1.61.02-3.1.94-3.93 2.38-1.68 2.91-.43 7.22 1.2 9.58.8 1.16 1.75 2.46 3 2.41 1.21-.05 1.67-.78 3.13-.78 1.46 0 1.87.78 3.14.75 1.3-.02 2.12-1.18 2.91-2.35.92-1.35 1.3-2.66 1.32-2.73-.03-.01-2.53-.97-2.55-3.85zM26.3 12.96c.66-.81 1.11-1.92.99-3.04-.96.04-2.13.64-2.82 1.44-.61.71-1.15 1.85-1.01 2.93 1.07.08 2.17-.55 2.84-1.33z" fill="#fff"/>
                        <text x="42" y="16" fill="#fff" font-family="Arial,Helvetica,sans-serif" font-size="7" opacity=".9">{{ __('messages.download_now') }}</text>
                        <text x="42" y="30" fill="#fff" font-family="Arial,Helvetica,sans-serif" font-size="14" font-weight="600">App Store</text>
                    </svg>
                </a>
            </div>

            {{-- Alt copyright bandı --}}
            <div style="border-top:1px solid #2a3242; padding:22px 0; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:18px;">
                <div style="color:#7a8294; font-size:13.5px;">
                    Copyright © {{ date('Y') }} {{ $ayarlar->firma_adi ?? 'DN Grup Medya ve Teknoloji A.Ş.' }}
                </div>
                <div style="display:flex; align-items:center; gap:14px;">
                    <span style="color:#7a8294; font-size:13px;">{{ __('messages.accepted_payments') }}</span>
                    <div class="dn-pay-logos" style="display:flex; gap:8px; align-items:center;">
                        {{-- VISA --}}
                        <span class="dn-pay-card" title="Visa">
                            <svg viewBox="0 0 48 32" width="46" height="30" xmlns="http://www.w3.org/2000/svg">
                                <rect width="48" height="32" rx="4" fill="#fff"/>
                                <text x="24" y="21" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="13" font-weight="800" font-style="italic" fill="#1A1F71" letter-spacing="0.5">VISA</text>
                            </svg>
                        </span>
                        {{-- Mastercard --}}
                        <span class="dn-pay-card" title="Mastercard">
                            <svg viewBox="0 0 48 32" width="46" height="30" xmlns="http://www.w3.org/2000/svg">
                                <rect width="48" height="32" rx="4" fill="#fff"/>
                                <circle cx="20" cy="16" r="8" fill="#EB001B"/>
                                <circle cx="28" cy="16" r="8" fill="#F79E1B" fill-opacity="0.9"/>
                                <path d="M24 10.2a8 8 0 0 0 0 11.6 8 8 0 0 0 0-11.6z" fill="#FF5F00"/>
                            </svg>
                        </span>
                        {{-- Apple Pay --}}
                        <span class="dn-pay-card" title="Apple Pay">
                            <svg viewBox="0 0 48 32" width="46" height="30" xmlns="http://www.w3.org/2000/svg">
                                <rect width="48" height="32" rx="4" fill="#fff"/>
                                <path d="M14.9 12.1c.4-.5.67-1.18.6-1.86-.58.03-1.29.39-1.7.88-.37.43-.7 1.12-.61 1.78.65.05 1.31-.33 1.71-.8zM15.5 13.05c-.94-.06-1.74.53-2.19.53-.45 0-1.14-.5-1.88-.49-.97.01-1.86.56-2.36 1.43-1 1.74-.26 4.32.72 5.73.48.69 1.05 1.47 1.8 1.44.72-.03.99-.47 1.86-.47.87 0 1.11.47 1.87.45.78-.01 1.27-.7 1.75-1.4.55-.8.77-1.58.78-1.62-.02-.01-1.5-.58-1.52-2.3-.01-1.44 1.17-2.13 1.22-2.17-.67-.98-1.71-1.09-2.07-1.11l-.01.05z" fill="#000"/>
                                <text x="20" y="20" font-family="Arial,Helvetica,sans-serif" font-size="9" font-weight="600" fill="#000">Pay</text>
                            </svg>
                        </span>
                        {{-- Troy (TR yerel) --}}
                        <span class="dn-pay-card" title="Troy">
                            <svg viewBox="0 0 48 32" width="46" height="30" xmlns="http://www.w3.org/2000/svg">
                                <rect width="48" height="32" rx="4" fill="#fff"/>
                                <text x="24" y="20" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="11" font-weight="800" fill="#00AEEF">troy</text>
                            </svg>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mobil responsive + store/odeme hover --}}
        <style>
            .dn-store-badge{ display:inline-block; transition:transform .2s ease, filter .2s ease; }
            .dn-store-badge:hover{ transform:translateY(-2px); filter:drop-shadow(0 6px 14px rgba(0,0,0,.35)); }
            .dn-store-badge svg{ display:block; border-radius:6px; }
            .dn-pay-card{ display:inline-flex; transition:transform .2s ease; }
            .dn-pay-card:hover{ transform:translateY(-2px); }
            .dn-pay-card svg{ display:block; box-shadow:0 1px 3px rgba(0,0,0,.25); border-radius:4px; }
            @media (max-width: 600px){ .dn-pay-logos{ flex-wrap:wrap; } }
            @media (max-width: 1024px) {
                .footer .footer-grid {
                    grid-template-columns: 1fr 1fr !important;
                    gap: 36px !important;
                }
            }
            @media (max-width: 600px) {
                .footer .footer-grid {
                    grid-template-columns: 1fr !important;
                }
            }
        </style>
    </footer>

    <!-- BUTTON GO TOP -->
    <a href="#0" class="cd-top"><i class="mdi mdi-chevron-up"></i></a>

    <!-- Javascript -->
    <script src="{{ asset('tema/js/typed.js') }}"></script>
    <script src="{{ asset('tema/js/popper.min.js') }}"></script>
    <script src="{{ asset('tema/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('tema/js/idangerous.swiper.min.js') }}"></script>
    <script src="{{ asset('tema/js/jquery.countdown.js') }}"></script>
    <script src="{{ asset('tema/js/jquery.magnific-popup.min.js') }}"></script>
    <script src="{{ asset('tema/js/slick.min.js') }}"></script>
    <script src="{{ asset('tema/js/owl.carousel.min.js') }}"></script>
    <script src="{{ asset('tema/js/isotope.min.js') }}"></script>
    <script src="{{ asset('tema/js/wow.min.js') }}"></script>
    <script src="{{ asset('tema/js/filter.js') }}"></script>
    <script src="{{ asset('tema/js/sidebar.js') }}"></script>
    <script src="{{ asset('tema/js/wow-init.js') }}"></script>
    <script src="{{ asset('tema/js/scripts.js') }}"></script>

    @stack('scripts')

    {{-- 🔔 Üye bildirim zili polling --}}
    @if(($isUyeLoggedIn ?? false) || ($isBayiAdminLoggedIn ?? false))
    @if(Route::has('bildirimlerim.sayim'))
    <script>
    (function(){
        var SAYIM_URL = "{{ route('bildirimlerim.sayim') }}";
        var TUM_URL   = "{{ route('bildirimlerim') }}";
        var menu = document.getElementById('uyeBellMenu');
        var wrap = document.getElementById('uyeBellWrap');
        if (!wrap) return;

        window.uyeBellToggle = function(e){
            e.stopPropagation();
            if (!menu) return;
            menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
            if (menu.style.display === 'block') uyeBellYukle();
        };
        document.addEventListener('click', function(e){
            if (menu && wrap && !wrap.contains(e.target)) menu.style.display = 'none';
        });

        function esc(s){ var d=document.createElement('div'); d.textContent = s||''; return d.innerHTML; }

        function uyeBellYukle(){
            var list = document.getElementById('uyeBellList');
            fetch(SAYIM_URL, { headers:{'Accept':'application/json'} })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    var badge = document.getElementById('uyeBellBadge');
                    if (badge){
                        if (d.okunmamis > 0){ badge.textContent = d.okunmamis > 99 ? '99+' : d.okunmamis; badge.style.display='flex'; }
                        else { badge.style.display='none'; }
                    }
                    if (!list) return;
                    if (!d.bildirimler || d.bildirimler.length === 0){
                        list.innerHTML = '<div style="padding:24px;text-align:center;color:#999;font-size:13px">Bildirim yok</div>';
                        return;
                    }
                    var html = '';
                    d.bildirimler.forEach(function(b){
                        var href = b.link ? b.link : TUM_URL;
                        html += '<a class="yt-bell-item '+(b.okundu?'':'unread')+'" href="'+href+'">'
                              + '<div class="b-title">'+esc(b.baslik)+'</div>'
                              + (b.mesaj ? '<div class="b-msg">'+esc(b.mesaj).slice(0,110)+'</div>' : '')
                              + (b.zaman ? '<div class="b-time">⏱ '+esc(b.zaman)+'</div>' : '')
                              + '</a>';
                    });
                    list.innerHTML = html;
                })
                .catch(function(){
                    if (list) list.innerHTML = '<div style="padding:20px;text-align:center;color:#c33;font-size:12px">Yüklenemedi</div>';
                });
        }

        // Sayfa açılınca + her 60 sn'de bir badge'i güncelle
        uyeBellYukle();
        setInterval(uyeBellYukle, 60000);
    })();
    </script>
    @endif
    @endif

    <!-- Master Layout JavaScript -->
    <meta name="change-currency-url" content="{{ route('change.currency', ['currency' => '__currency__']) }}">
    @if(request()->routeIs('domain.sorgula') || request()->has('domain'))
    <meta name="domain-sorgula-url" content="{{ route('domain.sorgula') }}">
    <meta name="sepet-domain-url" content="{{ route('sepet.domain.ekle') }}">
    @endif
    <script src="{{ asset('tema/js/master-layout.js') }}"></script>
    <script src="{{ asset('tema/js/language-preserve.js') }}"></script>

    <!-- Google Website Translator: widget + manual language dropdown entegrasyonu -->
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    <!-- ===== WhatsApp Float Buton (eski siteden tasindi) ===== -->
    <a href="https://wa.me/902129930218" target="_blank" rel="noopener" class="dn-whatsapp-float" title="WhatsApp" aria-label="WhatsApp">
        <i class="mdi mdi-whatsapp"></i>
        <span class="dn-whatsapp-tooltip">WhatsApp</span>
    </a>
    <style>
        .dn-whatsapp-float{
            position:fixed; right:22px; bottom:22px; z-index:9999;
            width:56px; height:56px; border-radius:50%;
            background:#25D366; color:#fff;
            display:flex; align-items:center; justify-content:center;
            box-shadow:0 6px 18px rgba(37,211,102,.45);
            text-decoration:none; transition:transform .2s ease, box-shadow .2s ease;
        }
        .dn-whatsapp-float i{ font-size:32px; line-height:1; color:#fff; }
        .dn-whatsapp-float:hover{ transform:scale(1.08); box-shadow:0 8px 24px rgba(37,211,102,.6); color:#fff; }
        .dn-whatsapp-tooltip{
            position:absolute; right:68px; top:50%; transform:translateY(-50%);
            background:#1a2332; color:#fff; padding:6px 12px; border-radius:6px;
            font-size:13px; white-space:nowrap; opacity:0; pointer-events:none;
            transition:opacity .2s ease;
        }
        .dn-whatsapp-float:hover .dn-whatsapp-tooltip{ opacity:1; }
        @media (max-width:600px){
            .dn-whatsapp-float{ width:50px; height:50px; right:16px; bottom:16px; }
            .dn-whatsapp-float i{ font-size:28px; }
            .dn-whatsapp-tooltip{ display:none; }
        }
    </style>
    </body>
    </html>