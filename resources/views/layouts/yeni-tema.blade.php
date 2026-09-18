<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        $ayarlar = DB::table('ayarlar')->first();
        $menuler = DB::table('menu')->where('menu_ust', 0)->orderBy('id')->get();
        $isLoggedIn = Auth::guard('uye')->check() && session('uye_logged_in');
        $siteTitle   = $ayarlar->site_baslik ?? 'İş Ortağım — Web Hosting & Domain';
        $logoFile    = $ayarlar->logo ?? 'site-logo.png';
        $logo_path   = asset('tema/uploads/logo/' . $logoFile);
        $favicon_path = $ayarlar && !empty($ayarlar->favicon)
            ? asset('tema/uploads/logo/' . $ayarlar->favicon)
            : $logo_path;
        $firmaAdi    = $ayarlar->firma_adi ?? 'İş Ortağım';
    @endphp

    <title>@yield('title', $siteTitle)</title>
    <meta name="description" content="@yield('description', $ayarlar->meta_aciklama ?? 'Web hosting, domain tescil ve dijital hizmetler.')">
    <meta name="keywords" content="@yield('keywords', 'web hosting, domain, ssl, web paketi, e-posta hosting')">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', $siteTitle)">
    <meta property="og:image" content="{{ $logo_path }}">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Favicon -->
    <link rel="shortcut icon" href="{{ $favicon_path }}" type="image/x-icon">
    <link rel="icon" type="image/png" href="{{ $favicon_path }}">
    <meta name="theme-color" content="#f5c000">

    <!-- Google Fonts: Inter + Syne -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Syne:wght@600;700;800&display=swap" rel="stylesheet">

    <!-- Material Design Icons -->
    <link rel="stylesheet" href="{{ asset('yonetim/vendors/iconfonts/mdi/font/css/materialdesignicons.min.css') }}">

    <!-- Yeni Tema CSS -->
    <link rel="stylesheet" href="{{ asset('tema/css/yeni-tema.css') }}">

    @stack('styles')
    @yield('head')
</head>
<body>

<!-- ===================== HEADER ===================== -->
<header class="yt-header" id="yt-header">
    <div class="yt-container">
        <nav class="yt-nav-inner">

            <!-- Logo -->
            <a href="{{ localized_route('anasayfa') }}" class="yt-logo">
                @if($ayarlar && !empty($ayarlar->logo))
                    <img src="{{ $logo_path }}" alt="{{ $firmaAdi }}" style="height:36px; width:auto; object-fit:contain;">
                @else
                    <div class="yt-logo-mark">İO</div>
                    <div class="yt-logo-text">
                        <span class="yt-logo-name">{{ $firmaAdi }}</span>
                        <span class="yt-logo-sub">Web Hosting & Domain</span>
                    </div>
                @endif
            </a>

            <!-- Desktop Nav -->
            <div class="yt-nav">
                @if($menuler && $menuler->count() > 0)
                    @foreach($menuler as $menu)
                        <a href="{{ $menu->link ?? '#' }}"
                           class="{{ request()->is(ltrim($menu->link ?? '', '/')) ? 'active' : '' }}">
                            {{ \App\Helpers\TranslationHelper::translate($menu->adi ?? '') }}
                        </a>
                    @endforeach
                @else
                    <a href="{{ localized_route('anasayfa') }}" class="{{ request()->routeIs('anasayfa') ? 'active' : '' }}">Ana Sayfa</a>
                    <a href="{{ localized_route('hosting') }}" class="{{ request()->routeIs('hosting') ? 'active' : '' }}">Hosting</a>
                    <a href="{{ localized_route('paketler') }}" class="{{ request()->routeIs('paketler') ? 'active' : '' }}">Web Paketleri</a>
                    <a href="{{ localized_route('blog') }}" class="{{ request()->routeIs('blog') ? 'active' : '' }}">Blog</a>
                    <a href="{{ localized_route('referanslar') }}" class="{{ request()->routeIs('referanslar') ? 'active' : '' }}">Referanslar</a>
                    <a href="{{ localized_route('iletisim') }}" class="{{ request()->routeIs('iletisim') ? 'active' : '' }}">İletişim</a>
                @endif
            </div>

            <!-- Actions -->
            <div class="yt-header-actions">
                @if($isLoggedIn)
                    <a href="{{ localized_route('hesabim') }}" class="yt-btn-ghost">
                        <i class="mdi mdi-account-circle"></i> Hesabım
                    </a>
                    <a href="{{ localized_route('cikis') }}" class="yt-btn-yellow">
                        <i class="mdi mdi-logout"></i> Çıkış
                    </a>
                @else
                    <a href="{{ localized_route('giris') }}" class="yt-btn-ghost">
                        <i class="mdi mdi-login"></i> Giriş Yap
                    </a>
                    <a href="{{ localized_route('kayit') }}" class="yt-btn-yellow">
                        <i class="mdi mdi-account-plus"></i> Kayıt Ol
                    </a>
                @endif

                <!-- Hamburger -->
                <button class="yt-hamburger" id="yt-hamburger" aria-label="Menüyü aç">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>

        </nav>
    </div>
</header>

<!-- Mobile Menu Overlay -->
<div class="yt-mobile-menu" id="yt-mobile-menu">
    <button class="yt-mobile-close" id="yt-mobile-close" aria-label="Kapat">&#10005;</button>

    @if($menuler && $menuler->count() > 0)
        @foreach($menuler as $menu)
            <a href="{{ $menu->link ?? '#' }}" class="{{ request()->is(ltrim($menu->link ?? '', '/')) ? 'active' : '' }}">
                {{ \App\Helpers\TranslationHelper::translate($menu->adi ?? '') }}
            </a>
        @endforeach
    @else
        <a href="{{ localized_route('anasayfa') }}">Ana Sayfa</a>
        <a href="{{ localized_route('hosting') }}">Hosting</a>
        <a href="{{ localized_route('paketler') }}">Web Paketleri</a>
        <a href="{{ localized_route('blog') }}">Blog</a>
        <a href="{{ localized_route('referanslar') }}">Referanslar</a>
        <a href="{{ localized_route('iletisim') }}">İletişim</a>
    @endif

    <div class="yt-mobile-actions" style="margin-top:32px;">
        @if($isLoggedIn)
            <a href="{{ localized_route('hesabim') }}" class="yt-btn-ghost" style="justify-content:center;">
                <i class="mdi mdi-account-circle"></i> Hesabım
            </a>
            <a href="{{ localized_route('cikis') }}" class="yt-btn-yellow" style="justify-content:center;">
                <i class="mdi mdi-logout"></i> Çıkış Yap
            </a>
        @else
            <a href="{{ localized_route('giris') }}" class="yt-btn-ghost" style="justify-content:center;">
                <i class="mdi mdi-login"></i> Giriş Yap
            </a>
            <a href="{{ localized_route('kayit') }}" class="yt-btn-yellow" style="justify-content:center;">
                <i class="mdi mdi-account-plus"></i> Kayıt Ol
            </a>
        @endif
    </div>
</div>

<!-- ===================== CONTENT ===================== -->
<main>
    @yield('content')
</main>

<!-- ===================== FOOTER ===================== -->
<footer class="yt-footer">
    <div class="yt-container">
        <div class="yt-footer-grid">

            <!-- Brand -->
            <div class="yt-footer-brand">
                <a href="{{ localized_route('anasayfa') }}" class="yt-logo" style="margin-bottom:16px; display:inline-flex;">
                    @if($ayarlar && !empty($ayarlar->logo))
                        <img src="{{ $logo_path }}" alt="{{ $firmaAdi }}" style="height:34px; width:auto; object-fit:contain; filter:brightness(10);">
                    @else
                        <div class="yt-logo-mark">İO</div>
                        <div class="yt-logo-text">
                            <span class="yt-logo-name">{{ $firmaAdi }}</span>
                            <span class="yt-logo-sub">Web Hosting & Domain</span>
                        </div>
                    @endif
                </a>
                <p>{{ $ayarlar->meta_aciklama ?? 'Dijital varlığınız için güvenilir hosting, domain ve web çözümleri sunuyoruz.' }}</p>
                <div class="yt-footer-social">
                    @if($ayarlar && !empty($ayarlar->facebook))
                        <a href="{{ $ayarlar->facebook }}" target="_blank" rel="noopener" aria-label="Facebook"><i class="mdi mdi-facebook"></i></a>
                    @endif
                    @if($ayarlar && !empty($ayarlar->twitter))
                        <a href="{{ $ayarlar->twitter }}" target="_blank" rel="noopener" aria-label="Twitter/X"><i class="mdi mdi-twitter"></i></a>
                    @endif
                    @if($ayarlar && !empty($ayarlar->instagram))
                        <a href="{{ $ayarlar->instagram }}" target="_blank" rel="noopener" aria-label="Instagram"><i class="mdi mdi-instagram"></i></a>
                    @endif
                    @if($ayarlar && !empty($ayarlar->linkedin))
                        <a href="{{ $ayarlar->linkedin }}" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="mdi mdi-linkedin"></i></a>
                    @endif
                    @if($ayarlar && !empty($ayarlar->youtube))
                        <a href="{{ $ayarlar->youtube }}" target="_blank" rel="noopener" aria-label="YouTube"><i class="mdi mdi-youtube"></i></a>
                    @endif
                    @if(!($ayarlar && (!empty($ayarlar->facebook) || !empty($ayarlar->twitter) || !empty($ayarlar->instagram))))
                        <a href="#" aria-label="Facebook"><i class="mdi mdi-facebook"></i></a>
                        <a href="#" aria-label="Twitter"><i class="mdi mdi-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="mdi mdi-instagram"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="mdi mdi-linkedin"></i></a>
                    @endif
                </div>
            </div>

            <!-- Hizmetler -->
            <div class="yt-footer-col">
                <h5>Hizmetler</h5>
                <ul>
                    <li><a href="{{ localized_route('domain-tescil') }}">Domain Tescil</a></li>
                    <li><a href="{{ localized_route('hosting') }}">Web Hosting</a></li>
                    <li><a href="{{ localized_route('paketler') }}">Web Paketleri</a></li>
                    <li><a href="{{ localized_route('hosting') }}">SSL Sertifikası</a></li>
                    <li><a href="{{ localized_route('hosting') }}">E-Posta Hosting</a></li>
                    <li><a href="{{ localized_route('bayi-basvuru') }}">Bayilik</a></li>
                </ul>
            </div>

            <!-- Kurumsal -->
            <div class="yt-footer-col">
                <h5>Kurumsal</h5>
                <ul>
                    <li><a href="{{ localized_route('referanslar') }}">Referanslar</a></li>
                    <li><a href="{{ localized_route('blog') }}">Blog</a></li>
                    <li><a href="{{ localized_route('iletisim') }}">İletişim</a></li>
                    @if(Route::has('sayfa'))
                        <li><a href="{{ localized_route('sayfa', ['slug' => 'hakkimizda']) }}">Hakkımızda</a></li>
                        <li><a href="{{ localized_route('sayfa', ['slug' => 'kvkk']) }}">KVKK</a></li>
                        <li><a href="{{ localized_route('sayfa', ['slug' => 'gizlilik']) }}">Gizlilik Politikası</a></li>
                    @endif
                </ul>
            </div>

            <!-- İletişim -->
            <div class="yt-footer-col">
                <h5>İletişim</h5>
                <ul>
                    @if($ayarlar && !empty($ayarlar->firma_telefon))
                        <li>
                            <a href="tel:{{ $ayarlar->firma_telefon }}">
                                <i class="mdi mdi-phone" style="margin-right:6px;"></i>{{ $ayarlar->firma_telefon }}
                            </a>
                        </li>
                    @endif
                    @if($ayarlar && !empty($ayarlar->firma_eposta))
                        <li>
                            <a href="mailto:{{ $ayarlar->firma_eposta }}">
                                <i class="mdi mdi-email" style="margin-right:6px;"></i>{{ $ayarlar->firma_eposta }}
                            </a>
                        </li>
                    @endif
                    @if($ayarlar && !empty($ayarlar->firma_adres))
                        <li>
                            <span style="color:rgba(255,255,255,.6); font-size:14px;">
                                <i class="mdi mdi-map-marker" style="margin-right:6px;"></i>{{ $ayarlar->firma_adres }}
                            </span>
                        </li>
                    @endif
                    <li>
                        <a href="{{ localized_route('iletisim') }}" class="yt-btn-yellow yt-btn-sm" style="margin-top:8px; display:inline-flex;">
                            Bize Ulaşın <i class="mdi mdi-arrow-right"></i>
                        </a>
                    </li>
                </ul>
            </div>

        </div>

        <!-- Footer Bottom -->
        <div class="yt-footer-bottom">
            <p>&copy; {{ date('Y') }} {{ $firmaAdi }}. Tüm hakları saklıdır.</p>
            <p>
                <a href="{{ localized_route('sayfa', ['slug' => 'gizlilik']) }}" style="margin-right:16px;">Gizlilik</a>
                <a href="{{ localized_route('sayfa', ['slug' => 'kvkk']) }}">KVKK</a>
            </p>
        </div>
    </div>
</footer>

@stack('modals')

<!-- Header scroll + hamburger JS -->
<script>
(function () {
    var header = document.getElementById('yt-header');
    var hamburger = document.getElementById('yt-hamburger');
    var mobileMenu = document.getElementById('yt-mobile-menu');
    var mobileClose = document.getElementById('yt-mobile-close');

    // Scroll shadow
    window.addEventListener('scroll', function () {
        if (window.scrollY > 20) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Open mobile menu
    hamburger.addEventListener('click', function () {
        hamburger.classList.add('open');
        mobileMenu.classList.add('open');
        document.body.style.overflow = 'hidden';
    });

    // Close mobile menu
    function closeMobile() {
        hamburger.classList.remove('open');
        mobileMenu.classList.remove('open');
        document.body.style.overflow = '';
    }
    mobileClose.addEventListener('click', closeMobile);

    // Close on link click
    mobileMenu.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeMobile);
    });
})();
</script>

@stack('scripts')
</body>
</html>
