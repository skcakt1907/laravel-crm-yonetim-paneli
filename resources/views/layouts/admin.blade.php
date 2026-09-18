<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        /** @var \stdClass|null $ayarlar */
        $ayarlar = DB::table('ayarlar')->first();
        $adminTitle = $ayarlar->site_baslik ?? 'DN Kreatif İş Ortağım - Yönetim Paneli';
        $adminLogoPath = $ayarlar && !empty($ayarlar->firma_logo)
            ? asset('tema/uploads/logo/'.$ayarlar->firma_logo)
            : asset('tema/uploads/logo/logo.png');
        $adminFaviconPath = $ayarlar && !empty($ayarlar->favicon)
            ? asset('tema/uploads/logo/'.$ayarlar->favicon).'?v='.($ayarlar->updated_at ? strtotime($ayarlar->updated_at) : time())
            : $adminLogoPath;
    @endphp
    <title>@yield('title', $adminTitle)</title>
    
    <!-- Vendors CSS -->
    <link rel="stylesheet" href="{{ asset('yonetim/vendors/iconfonts/mdi/font/css/materialdesignicons.min.css') }}">
    <link rel="stylesheet" href="{{ asset('yonetim/vendors/css/vendor.bundle.base.css') }}">
    <link rel="stylesheet" href="{{ asset('yonetim/vendors/css/vendor.bundle.addons.css') }}">
    <link rel="stylesheet" href="{{ asset('yonetim/vendors/iconfonts/ti-icons/css/themify-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('yonetim/vendors/iconfonts/simple-line-icon/css/simple-line-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('yonetim/vendors/iconfonts/font-awesome/css/font-awesome.min.css') }}">
    
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('yonetim/css/vertical-layout-light/style.css') }}">
    
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/png" href="{{ $adminFaviconPath }}" />
    <link rel="icon" type="image/png" href="{{ $adminFaviconPath }}" />
    
    <!-- Admin Layout CSS -->
    <link rel="stylesheet" href="{{ asset('yonetim/css/admin-layout.css') }}">
    <link rel="stylesheet" href="{{ asset('yonetim/css/admin-buttons.css') }}?v={{ filemtime(public_path('yonetim/css/admin-buttons.css')) }}">
    <link rel="stylesheet" href="{{ asset('yonetim/css/admin-mobile.css') }}?v={{ filemtime(public_path('yonetim/css/admin-mobile.css')) }}">
    <link rel="stylesheet" href="{{ asset('yonetim/css/admin-polish.css') }}?v={{ filemtime(public_path('yonetim/css/admin-polish.css')) }}">
    
    @stack('styles')
</head>
<body>
    @if(config('app.debug') && config('app.env') !== 'local')
    <div style="background: linear-gradient(90deg, #ef4444, #dc2626); color: white; padding: 10px 20px; text-align: center; font-weight: 600; font-size: 14px; position: fixed; top: 0; left: 0; right: 0; z-index: 99999;">
        <i class="mdi mdi-alert-circle"></i> UYARI: Debug modu açık! Production'da kapatın: <code style="background: rgba(0,0,0,0.2); padding: 2px 8px; border-radius: 4px;">APP_DEBUG=false</code>
    </div>
    @endif
    <div class="container-scroller" @if(config('app.debug') && config('app.env') !== 'local') style="margin-top: 45px;" @endif>
        <!-- Navbar -->
        <nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                <a class="navbar-brand brand-logo" href="{{ route('admin.dashboard') }}" style="font-size: 20px; font-weight: bold; color: #6f42c1;">
                    <i class="mdi mdi-monitor-dashboard"></i> {{ __('messages.admin_panel') }}
                </a>
                <a class="navbar-brand brand-logo-mini" href="{{ route('admin.dashboard') }}" style="font-size: 18px; font-weight: bold; color: #6f42c1;">
                    <i class="mdi mdi-monitor-dashboard"></i>
                </a>
            </div>
            <div class="navbar-menu-wrapper d-flex align-items-stretch">
                <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
                    <span class="mdi mdi-menu"></span>
                </button>
                
                <ul class="navbar-nav navbar-nav-right ml-auto">
                    <!-- Dil Değiştirici -->
                    <li class="nav-item" id="languageDropdownWrapper">
                        <a class="nav-link count-indicator d-flex align-items-center" id="languageDropdown" href="javascript:void(0);" onclick="toggleLanguageMenu(event)">
                            @php
                                $current_lang = app()->getLocale();
                                $lang_icon = match($current_lang) {
                                    'tr' => '🇹🇷',
                                    'en' => '🇬🇧',
                                    'ar' => '🇸🇦',
                                    default => '🌐'
                                };
                                $lang_name = match($current_lang) {
                                    'tr' => 'Türkçe',
                                    'en' => 'English',
                                    'ar' => 'العربية',
                                    default => 'Language'
                                };
                            @endphp
                            <i class="mdi mdi-translate"></i>
                            <span class="ml-1 d-none d-md-inline">{{ $lang_icon }} {{ $lang_name }}</span>
                            <span class="ml-1 d-md-none">{{ $lang_icon }}</span>
                        </a>
                        <div class="custom-language-dropdown" id="languageDropdownMenu" style="display: none;">
                            <h6 class="p-3 mb-0">Dil Seç / Language / اللغة</h6>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item preview-item" href="{{ route('change.language', ['lang' => 'tr']) }}">
                                <div class="preview-item-content flex-grow">
                                    <span class="preview-subject">🇹🇷 Türkçe</span>
                                </div>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item preview-item" href="{{ route('change.language', ['lang' => 'en']) }}">
                                <div class="preview-item-content flex-grow">
                                    <span class="preview-subject">🇬🇧 English</span>
                                </div>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item preview-item" href="{{ route('change.language', ['lang' => 'ar']) }}">
                                <div class="preview-item-content flex-grow">
                                    <span class="preview-subject">🇸🇦 العربية</span>
                                </div>
                            </a>
                        </div>
                    </li>

                    <!-- Para Birimi Değiştirici -->
                    <li class="nav-item" id="currencyDropdownWrapper">
                        <a class="nav-link count-indicator d-flex align-items-center" id="currencyDropdown" href="javascript:void(0);" onclick="toggleCurrencyMenu(event)">
                            @php
                                $current_currency = request('currency') ?? session('currency', 'TRY');
                                $current_currency = strtoupper($current_currency);
                                $currency_symbol = match($current_currency) {
                                    'USD' => '$',
                                    'EUR' => '€',
                                    'AED' => 'د.إ',
                                    default => '₺'
                                };
                            @endphp
                            <i class="mdi mdi-currency-usd"></i>
                            <span class="ml-1 d-none d-md-inline">{{ $currency_symbol }} {{ strtoupper($current_currency) }}</span>
                            <span class="ml-1 d-md-none">{{ $currency_symbol }}</span>
                        </a>
                        <div class="custom-language-dropdown" id="currencyDropdownMenu" style="display: none;">
                            <h6 class="p-3 mb-0">{{ __('messages.currency_select') }}</h6>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item preview-item" href="{{ request()->fullUrlWithQuery(['currency' => 'TRY', 'lang' => app()->getLocale()]) }}">
                                <div class="preview-item-content flex-grow">
                                    <span class="preview-subject">{{ __('messages.currency_try') }}</span>
                                </div>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item preview-item" href="{{ request()->fullUrlWithQuery(['currency' => 'USD', 'lang' => app()->getLocale()]) }}">
                                <div class="preview-item-content flex-grow">
                                    <span class="preview-subject">{{ __('messages.currency_usd') }}</span>
                                </div>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item preview-item" href="{{ request()->fullUrlWithQuery(['currency' => 'EUR', 'lang' => app()->getLocale()]) }}">
                                <div class="preview-item-content flex-grow">
                                    <span class="preview-subject">{{ __('messages.currency_eur') }}</span>
                                </div>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item preview-item" href="{{ request()->fullUrlWithQuery(['currency' => 'AED', 'lang' => app()->getLocale()]) }}">
                                <div class="preview-item-content flex-grow">
                                    <span class="preview-subject">{{ __('messages.currency_aed') }}</span>
                                </div>
                            </a>
                        </div>
                    </li>
                    
                    <li class="nav-item nav-profile dropdown" id="profileDropdownWrapper">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" id="profileDropdown" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <div class="nav-profile-img">
                                <img src="{{ asset('yonetim/images/users/avatar.jpg') }}" alt="image">
                                <span class="availability-status online"></span>
                            </div>
                            <div class="nav-profile-text ml-2 d-none d-md-block">
                                <p class="mb-0 text-black">{{ session('admin_adi') }}</p>
                                <small class="text-muted">
                                    @php
                                        $rol_badge = [
                                            1 => '👑 Patron',
                                            2 => '👤 Çalışan',
                                            3 => '🤝 Bayi',
                                            4 => '👥 Müşteri',
                                            5 => '💼 Muhasebe'
                                        ];
                                    @endphp
                                    {{ $rol_badge[session('admin_rol', 2)] ?? 'Kullanıcı' }}
                                </small>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="profileDropdown" id="profileDropdownMenu">
                            <div class="dropdown-header text-center">
                                <img class="img-md rounded-circle" src="{{ asset('yonetim/images/users/avatar.jpg') }}" alt="Profile image" style="width: 50px; height: 50px;">
                                <p class="mb-1 mt-3 font-weight-semibold">{{ session('admin_adi') }}</p>
                                <p class="font-weight-light text-muted mb-0">{{ session('admin_kullanici_adi') }}</p>
                            </div>
                            <a class="dropdown-item" href="{{ route('admin.profil') }}">
                                <i class="mdi mdi-account-settings mr-2 text-info"></i> Profil Ayarları
                            </a>
                            <a class="dropdown-item" href="{{ url('/') }}" target="_blank">
                                <i class="mdi mdi-earth mr-2 text-success"></i> Siteyi Görüntüle
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('admin.cikis') }}">
                                <i class="mdi mdi-logout mr-2 text-primary"></i> Çıkış Yap
                            </a>
                        </div>
                    </li>
                </ul>
                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="offcanvas">
                    <span class="mdi mdi-menu"></span>
                </button>
            </div>
        </nav>
        
        <div class="container-fluid page-body-wrapper">
            <!-- Sidebar -->
            <nav class="sidebar sidebar-offcanvas" id="sidebar">
                <ul class="nav">
                    <li class="nav-item sidebar-category">
                        <span style="margin-left: -10px;">{{ __('messages.admin_panel') }}</span>
                    </li>
                    
                    <li class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.dashboard') }}">
                            <i class="mdi mdi-home-outline menu-icon"></i>
                            <span class="menu-title">{{ __('messages.home') }}</span>
                        </a>
                    </li>
                    
                    @php
                        $admin_rol = session('admin_rol', 2);
                    @endphp
                    
                    <!-- PATRON VE ÇALIŞAN İÇİN GÖRÜNÜR -->
                    @if($admin_rol == 1 || $admin_rol == 2 || $admin_rol == 5)
                    <!-- Site Yönetimi/Ayarlar -->
                    @php 
                        $ayarlar_has_access = can_access_page('admin.ayarlar.index') || 
                                              can_access_page('admin.ayarlar.api') || 
                                              can_access_page('admin.ayarlar.iletisim') || 
                                              can_access_page('admin.ayarlar.sosyal') || 
                                              can_access_page('admin.ayarlar.modul') || 
                                              can_access_page('admin.ayarlar.limit') || 
                                              can_access_page('admin.ayarlar.bakim') || 
                                              can_access_page('admin.ayarlar.mail') || 
                                              can_access_page('admin.ayarlar.sms') || 
                                              can_access_page('admin.ayarlar.sanal') || 
                                              can_access_page('admin.ayarlar.arkaplan') || 
                                              can_access_page('admin.kuponlar.index');
                    @endphp
                    @if($ayarlar_has_access)
                    @php $ayarlar_active = request()->routeIs('admin.ayarlar.*') || request()->routeIs('admin.kuponlar.*'); @endphp
                    <li class="nav-item {{ $ayarlar_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#ayarlar" aria-expanded="{{ $ayarlar_active ? 'true' : 'false' }}" aria-controls="ayarlar">
                            <i class="mdi mdi-cog-outline menu-icon"></i>
                            <span class="menu-title">Site Yönetimi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $ayarlar_active ? 'show' : '' }}" id="ayarlar">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.ayarlar.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.index') }}">Genel Ayarlar</a></li>
                                @endcanAccess
                                @canAccess('admin.ayarlar.api')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.api') }}">API Ayarları</a></li>
                                @endcanAccess
                                @canAccess('admin.ayarlar.iletisim')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.iletisim') }}">İletişim Ayarları</a></li>
                                @endcanAccess
                                @canAccess('admin.ayarlar.sosyal')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.sosyal') }}">Sosyal Medya Ayarları</a></li>
                                @endcanAccess
                                @canAccess('admin.ayarlar.modul')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.modul') }}">Modül Ayarları</a></li>
                                @endcanAccess
                                @canAccess('admin.ayarlar.limit')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.limit') }}">Limit Ayarları</a></li>
                                @endcanAccess
                                @canAccess('admin.ayarlar.bakim')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.bakim') }}">Site Bakım Modu</a></li>
                                @endcanAccess
                                @canAccess('admin.ayarlar.mail')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.mail') }}">Mail Ayarları</a></li>
                                @endcanAccess
                                @canAccess('admin.mail-templates.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.mail-templates.index') }}">Mail Şablonları</a></li>
                                @endcanAccess
                                @canAccess('admin.ayarlar.sms')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.sms') }}">SMS Ayarları</a></li>
                                @endcanAccess
                                @canAccess('admin.ayarlar.sanal')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.sanal') }}">Sanal Pos Ayarları</a></li>
                                @endcanAccess
                                @canAccess('admin.ayarlar.arkaplan')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.ayarlar.arkaplan') }}">Arkaplan Ayarları</a></li>
                                @endcanAccess
                                @canAccess('admin.kuponlar.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.kuponlar.index') }}">Kuponlar</a></li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    <!-- Dil Yönetimi -->
                    @php 
                        $diller_has_access = can_access_page('admin.diller.ekle') || can_access_page('admin.diller.index');
                    @endphp
                    @if($diller_has_access)
                    @php $diller_active = request()->routeIs('admin.diller.*'); @endphp
                    <li class="nav-item {{ $diller_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#diller" aria-expanded="{{ $diller_active ? 'true' : 'false' }}" aria-controls="diller">
                            <i class="mdi mdi-translate menu-icon"></i>
                            <span class="menu-title">Dil Yönetimi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $diller_active ? 'show' : '' }}" id="diller">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.diller.ekle')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.diller.ekle') }}">Dil Ekle</a></li>
                                @endcanAccess
                                @canAccess('admin.diller.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.diller.index') }}">Dil Listele</a></li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    <!-- Menü Yönetimi -->
                    @php 
                        $menuler_has_access = can_access_page('admin.menuler.header') || can_access_page('admin.menuler.footer');
                    @endphp
                    @if($menuler_has_access)
                    @php $menuler_active = request()->routeIs('admin.menuler.*'); @endphp
                    <li class="nav-item {{ $menuler_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#menuler" aria-expanded="{{ $menuler_active ? 'true' : 'false' }}" aria-controls="menuler">
                            <i class="mdi mdi-menu menu-icon"></i>
                            <span class="menu-title">Menü Yönetimi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $menuler_active ? 'show' : '' }}" id="menuler">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.menuler.header')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.menuler.header') }}">Header Menu</a></li>
                                @endcanAccess
                                @canAccess('admin.menuler.footer')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.menuler.footer') }}">Footer Menu</a></li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    

                    @php 
                        $crm_active = request()->routeIs('admin.crm.*');
                        $crm_has_access = can_access_page('admin.crm.musteriler.index') ||
                                          can_access_page('admin.crm.firsatlar.index') ||
                                          can_access_page('admin.crm.gorevler.index') ||
                                          can_access_page('admin.crm.kanban.index') ||
                                          can_access_page('admin.crm.domains.index');
                    @endphp
                    @if($crm_has_access)
                    <li class="nav-item {{ $crm_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#crmMenu" aria-expanded="{{ $crm_active ? 'true' : 'false' }}" aria-controls="crmMenu">
                            <i class="mdi mdi-chart-line menu-icon"></i>
                            <span class="menu-title">CRM Yönetimi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $crm_active ? 'show' : '' }}" id="crmMenu">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.crm.musteriler.index')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.crm.musteriler.index') }}">Müşteriler</a>
                                </li>
                                @endcanAccess
                                @canAccess('admin.crm.firsatlar.index')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.crm.firsatlar.index') }}">Fırsatlar</a>
                                </li>
                                @endcanAccess
                                @canAccess('admin.crm.gorevler.index')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.crm.gorevler.index') }}">Görevler</a>
                                </li>
                                @endcanAccess
                                @canAccess('admin.crm.kanban.index')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.crm.kanban.index') }}">Kanban Board</a>
                                </li>
                                @endcanAccess
                                @canAccess('admin.crm.domains.index')
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('admin.crm.domains.index') }}">Domain Takip</a>
                                </li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    <!-- Muhasebe -->
                    @php 
                        $muhasebe_has_access = can_access_page('admin.faturalar.bekleyen') || 
                                               can_access_page('admin.faturalar.onaylanan') || 
                                               can_access_page('admin.faturalar.index') || 
                                               can_access_page('admin.banka.index');
                    @endphp
                    @if($muhasebe_has_access)
                    @php $muhasebe_active = request()->routeIs('admin.faturalar.*') || request()->routeIs('admin.banka.*'); @endphp
                    <li class="nav-item {{ $muhasebe_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#muhasebe" aria-expanded="{{ $muhasebe_active ? 'true' : 'false' }}" aria-controls="muhasebe">
                            <i class="mdi mdi-cash-multiple menu-icon"></i>
                            <span class="menu-title">Muhasebe</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $muhasebe_active ? 'show' : '' }}" id="muhasebe">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.faturalar.bekleyen')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.faturalar.bekleyen') }}">Bekleyen Faturalar</a></li>
                                @endcanAccess
                                @canAccess('admin.faturalar.onaylanan')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.faturalar.onaylanan') }}">Onaylanan Faturalar</a></li>
                                @endcanAccess
                                @canAccess('admin.faturalar.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.faturalar.index') }}">Tüm Faturalar</a></li>
                                @endcanAccess
                                @canAccess('admin.banka.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.banka.index') }}">Banka Hesapları</a></li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    @php 
                        $destek_active = request()->routeIs('admin.destek*') || request()->routeIs('admin.iletisim*');
                        $destek_has_access = can_access_page('admin.destek.index') || can_access_page('admin.iletisim.index');
                    @endphp
                    @if($destek_has_access)
                    <li class="nav-item {{ $destek_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#destekMerkezi" aria-expanded="{{ $destek_active ? 'true' : 'false' }}" aria-controls="destekMerkezi">
                            <i class="mdi mdi-lifebuoy menu-icon"></i>
                            <span class="menu-title">Destek Merkezi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $destek_active ? 'show' : '' }}" id="destekMerkezi">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.destek.index')
                                <li class="nav-item"> 
                                    <a class="nav-link" href="{{ route('admin.destek.index') }}">Destek Talepleri</a>
                                </li>
                                @endcanAccess
                                @canAccess('admin.iletisim.index')
                                <li class="nav-item"> 
                                    <a class="nav-link" href="{{ route('admin.iletisim.index') }}">
                                        İletişim Mesajları
                                        @php
                                            $okunmamis_iletisim = DB::table('iletisim')->where('durum', 0)->count();
                                        @endphp
                                        @if($okunmamis_iletisim > 0)
                                            <span class="badge badge-danger ml-2">{{ $okunmamis_iletisim }}</span>
                                        @endif
                                    </a>
                                </li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    @canAccess('admin.mesajlar.index')
                    <li class="nav-item {{ request()->routeIs('admin.mesajlar*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.mesajlar.index') }}">
                            <i class="mdi mdi-email-outline menu-icon"></i>
                            <span class="menu-title">Mesajlar</span>
                            @php
                                $okunmamis_mesaj = DB::table('mesajlar')->where('durum', 0)->count();
                            @endphp
                            @if($okunmamis_mesaj > 0)
                            <span class="badge badge-danger ml-2">{{ $okunmamis_mesaj }}</span>
                            @endif
                        </a>
                    </li>
                    @endcanAccess
                    
                    <!-- Bayilik Yönetimi -->
                    @php 
                        $bayilik_has_access = can_access_page('admin.bayiler.index') || can_access_page('admin.bayilik.satislar');
                    @endphp
                    @if($bayilik_has_access)
                    @php $bayilik_active = request()->routeIs('admin.bayiler.*') || request()->routeIs('admin.bayilik.*'); @endphp
                    <li class="nav-item {{ $bayilik_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#bayilik" aria-expanded="{{ $bayilik_active ? 'true' : 'false' }}" aria-controls="bayilik">
                            <i class="mdi mdi-store-outline menu-icon"></i>
                            <span class="menu-title">Bayilik Yönetimi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $bayilik_active ? 'show' : '' }}" id="bayilik">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.bayiler.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.bayiler.index') }}">Bayilikler</a></li>
                                @endcanAccess
                                @canAccess('admin.bayilik.satislar')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.bayilik.satislar') }}">Bayilik Satışlar</a></li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    <li class="nav-item sidebar-category mt-4">
                        <span style="margin-left: -10px;">SATIŞ YÖNETİMİ</span>
                    </li>
                    
                    <!-- Hosting Yönetimi -->
                    @php 
                        $hosting_has_access = can_access_page('admin.satislar.hosting') || can_access_page('admin.hosting.paketler.index');
                    @endphp
                    @if($hosting_has_access)
                    @php $hosting_active = request()->routeIs('admin.satislar.hosting') || request()->routeIs('admin.hosting.*'); @endphp
                    <li class="nav-item {{ $hosting_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#hosting" aria-expanded="{{ $hosting_active ? 'true' : 'false' }}" aria-controls="hosting">
                            <i class="mdi mdi-server menu-icon"></i>
                            <span class="menu-title">Hosting Yönetimi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $hosting_active ? 'show' : '' }}" id="hosting">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.satislar.hosting')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.satislar.hosting') }}">Hosting Satışlar</a></li>
                                @endcanAccess
                                @canAccess('admin.hosting.paketler.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.hosting.paketler.index') }}">Hosting Paketler</a></li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    <!-- Alan Adı Yönetimi -->
                    @php 
                        $alanadi_has_access = can_access_page('admin.satislar.domain') || can_access_page('admin.domain.fiyatlar.index');
                    @endphp
                    @if($alanadi_has_access)
                    @php $alanadi_active = request()->routeIs('admin.satislar.domain') || request()->routeIs('admin.domain.*'); @endphp
                    <li class="nav-item {{ $alanadi_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#alanadi" aria-expanded="{{ $alanadi_active ? 'true' : 'false' }}" aria-controls="alanadi">
                            <i class="mdi mdi-earth menu-icon"></i>
                            <span class="menu-title">Alan Adı Yönetimi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $alanadi_active ? 'show' : '' }}" id="alanadi">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.satislar.domain')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.satislar.domain') }}">Alan Adı Satışlar</a></li>
                                @endcanAccess
                                @canAccess('admin.domain.fiyatlar.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.domain.fiyatlar.index') }}">Alan Adı Fiyatları</a></li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    <!-- Web Paket Yönetimi -->
                    @php 
                        $webpaket_has_access = can_access_page('admin.satislar.web-paket') || 
                                               can_access_page('admin.paketler.index') || 
                                               can_access_page('admin.paketler.ozel') ||
                                               can_access_page('admin.kategoriler.index');
                    @endphp
                    @if($webpaket_has_access)
                    @php $webpaket_active = request()->routeIs('admin.satislar.web-paket') || request()->routeIs('admin.paketler.*') || request()->routeIs('admin.kategoriler.*'); @endphp
                    <li class="nav-item {{ $webpaket_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#webpaket" aria-expanded="{{ $webpaket_active ? 'true' : 'false' }}" aria-controls="webpaket">
                            <i class="mdi mdi-layers menu-icon"></i>
                            <span class="menu-title">Web Paket Yönetimi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $webpaket_active ? 'show' : '' }}" id="webpaket">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.satislar.web-paket')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.satislar.web-paket') }}">Web Paket Satışlar</a></li>
                                @endcanAccess
                                @canAccess('admin.paketler.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.paketler.index') }}">Web Paketler</a></li>
                                @endcanAccess
                                @canAccess('admin.paketler.ozel')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.paketler.ozel') }}">Müşteriye Özel Teklifler</a></li>
                                @endcanAccess
                                @canAccess('admin.kategoriler.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.kategoriler.index') }}">Web Kategoriler</a></li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    <!-- Hizmet Yönetimi -->
                    @php 
                        $hizmet_has_access = can_access_page('admin.hizmetler.index') || can_access_page('admin.hizmetler.ekle');
                    @endphp
                    @if($hizmet_has_access)
                    @php $hizmet_active = request()->routeIs('admin.hizmetler.*'); @endphp
                    <li class="nav-item {{ $hizmet_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#hizmet" aria-expanded="{{ $hizmet_active ? 'true' : 'false' }}" aria-controls="hizmet">
                            <i class="mdi mdi-briefcase-outline menu-icon"></i>
                            <span class="menu-title">Hizmet Yönetimi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $hizmet_active ? 'show' : '' }}" id="hizmet">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.hizmetler.index')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.hizmetler.index') }}">Hizmet Listele</a></li>
                                @endcanAccess
                                @canAccess('admin.hizmetler.ekle')
                                <li class="nav-item"> <a class="nav-link" href="{{ route('admin.hizmetler.ekle') }}">Hizmet Ekle</a></li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    <!-- İçerik Yönetimi -->
                    <li class="nav-item sidebar-category mt-4">
                        <span style="margin-left: -10px;">İÇERİK YÖNETİMİ</span>
                    </li>
                    
                    @php 
                        $icerik_active = request()->routeIs('admin.sayfalar*') || request()->routeIs('admin.slider*') || request()->routeIs('admin.blog*') || request()->routeIs('admin.referanslar*') || request()->routeIs('admin.kampanyalar*') || request()->routeIs('admin.yorumlar*');
                        $icerik_has_access = can_access_page('admin.sayfalar.index') || 
                                            can_access_page('admin.slider.index') || 
                                            can_access_page('admin.blog.index') || 
                                            can_access_page('admin.referanslar.index') || 
                                            can_access_page('admin.kampanyalar.index') || 
                                            can_access_page('admin.yorumlar.index');
                    @endphp
                    @if($icerik_has_access)
                    <li class="nav-item {{ $icerik_active ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#icerik" aria-expanded="{{ $icerik_active ? 'true' : 'false' }}" aria-controls="icerik">
                        <i class="mdi mdi-file-document-outline menu-icon"></i>
                            <span class="menu-title">İçerikler</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ $icerik_active ? 'show' : '' }}" id="icerik">
                            <ul class="nav flex-column sub-menu">
                                @canAccess('admin.sayfalar.index')
                                <li class="nav-item"> <a class="nav-link {{ request()->routeIs('admin.sayfalar*') ? 'active' : '' }}" href="{{ route('admin.sayfalar.index') }}">Sayfalar</a></li>
                                @endcanAccess
                                @canAccess('admin.slider.index')
                                <li class="nav-item"> <a class="nav-link {{ request()->routeIs('admin.slider*') ? 'active' : '' }}" href="{{ route('admin.slider.index') }}">Slider</a></li>
                                @endcanAccess
                                @canAccess('admin.blog.index')
                                <li class="nav-item"> <a class="nav-link {{ request()->routeIs('admin.blog*') ? 'active' : '' }}" href="{{ route('admin.blog.index') }}">Blog</a></li>
                                @endcanAccess
                                @canAccess('admin.referanslar.index')
                                <li class="nav-item"> <a class="nav-link {{ request()->routeIs('admin.referanslar*') ? 'active' : '' }}" href="{{ route('admin.referanslar.index') }}">Referanslar</a></li>
                                @endcanAccess
                                @canAccess('admin.kampanyalar.index')
                                <li class="nav-item"> <a class="nav-link {{ request()->routeIs('admin.kampanyalar*') ? 'active' : '' }}" href="{{ route('admin.kampanyalar.index') }}">Kampanyalar / Fırsatlar</a></li>
                                @endcanAccess
                                @canAccess('admin.yorumlar.index')
                                <li class="nav-item"> <a class="nav-link {{ request()->routeIs('admin.yorumlar*') ? 'active' : '' }}" href="{{ route('admin.yorumlar.index') }}">Yorumlar</a></li>
                                @endcanAccess
                            </ul>
                        </div>
                    </li>
                    @endif
                    
                    <!-- Sadece Patron Görebilir -->
                    @if($admin_rol == 1)
                    @canAccess('admin.yoneticiler.index')
                    <li class="nav-item {{ request()->routeIs('admin.yoneticiler*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.yoneticiler.index') }}">
                            <i class="mdi mdi-account-circle-outline menu-icon"></i>
                            <span class="menu-title">Yöneticiler</span>
                        </a>
                    </li>
                    @endcanAccess
                    
                    @canAccess('admin.tickets.index')
                    <li class="nav-item {{ request()->routeIs('admin.tickets*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.tickets.index') }}">
                            <i class="mdi mdi-tag-multiple menu-icon"></i>
                            <span class="menu-title">Ticket Sistemi</span>
                            @php
                                // Patron ise tüm bekleyen ticketları, diğerleri için sadece kendilerine ait olanları say
                                if ($admin_rol == 1) {
                                    $okunmamis_ticket = DB::table('calisan_tickets')->where('durum', 0)->count();
                                } else {
                                    $okunmamis_ticket = DB::table('calisan_tickets')
                                        ->where(function($q) use ($admin_id) {
                                            $q->where('olusturan_id', $admin_id)
                                              ->orWhere('atanan_id', $admin_id);
                                        })
                                        ->where('durum', 0)
                                        ->count();
                                }
                            @endphp
                            @if($okunmamis_ticket > 0)
                            <span class="badge badge-danger ml-2">{{ $okunmamis_ticket }}</span>
                            @endif
                        </a>
                    </li>
                    @endcanAccess
                    
                    @canAccess('admin.import.export')
                    <li class="nav-item {{ request()->routeIs('admin.import*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.import.export') }}">
                            <i class="mdi mdi-cloud-upload-outline menu-icon"></i>
                            <span class="menu-title">Import/Export</span>
                        </a>
                    </li>
                    @endcanAccess
                    @endif
                    @endif
                    
                    <!-- BAYİ PANELİ - Sadeleştirilmiş Menü -->
                    @if($admin_rol == 3)
                    <li class="nav-item sidebar-category mt-4">
                        <span style="margin-left: -10px;">🤝 {{ __('messages.reseller_panel') }}</span>
                    </li>
                    
                    <li class="nav-item {{ request()->routeIs('admin.bayi.dashboard') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.bayi.dashboard') }}">
                            <i class="mdi mdi-view-dashboard menu-icon"></i>
                            <span class="menu-title">{{ __('messages.dashboard') }}</span>
                        </a>
                    </li>
                    
                    <li class="nav-item {{ request()->routeIs('admin.bayi.satislar*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.bayi.satislar') }}">
                            <i class="mdi mdi-cart menu-icon"></i>
                            <span class="menu-title">{{ __('messages.reseller_my_sales') }}</span>
                        </a>
                    </li>
                    
                    <li class="nav-item {{ request()->routeIs('admin.bayi.kazanclar*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.bayi.kazanclar') }}">
                            <i class="mdi mdi-wallet menu-icon"></i>
                            <span class="menu-title">{{ __('messages.reseller_my_earnings') }}</span>
                        </a>
                    </li>
                    
                    <li class="nav-item {{ request()->routeIs('admin.bayi.odeme.talepleri*') || request()->routeIs('admin.bayi.odeme.talep*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.bayi.odeme.talepleri') }}">
                            <i class="mdi mdi-cash-multiple menu-icon"></i>
                            <span class="menu-title">{{ __('messages.reseller_payment_requests') }}</span>
                        </a>
                    </li>
                    
                    <li class="nav-item {{ request()->routeIs('admin.bayi.musteriler*') || request()->routeIs('admin.bayi.musteri*') ? 'active' : '' }}">
                        <a class="nav-link" data-toggle="collapse" href="#bayiMusteriler" aria-expanded="{{ request()->routeIs('admin.bayi.musteriler*') || request()->routeIs('admin.bayi.musteri*') ? 'true' : 'false' }}" aria-controls="bayiMusteriler">
                            <i class="mdi mdi-account-multiple menu-icon"></i>
                            <span class="menu-title">{{ __('messages.reseller_my_customers') }}</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse {{ request()->routeIs('admin.bayi.musteriler*') || request()->routeIs('admin.bayi.musteri*') ? 'show' : '' }}" id="bayiMusteriler">
                            <ul class="nav flex-column sub-menu">
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.bayi.musteriler') ? 'active' : '' }}" href="{{ route('admin.bayi.musteriler') }}">
                                        {{ __('messages.reseller_my_customers') }}
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('admin.bayi.musteri.ekle') ? 'active' : '' }}" href="{{ route('admin.bayi.musteri.ekle') }}">
                                        <i class="mdi mdi-account-plus"></i> Yeni Müşteri Ekle
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    
                    <li class="nav-item {{ request()->routeIs('admin.bayi.referans.link*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.bayi.referans.link') }}">
                            <i class="mdi mdi-link-variant menu-icon"></i>
                            <span class="menu-title">{{ __('messages.reseller_referral_link') }}</span>
                        </a>
                    </li>
                    
                    <li class="nav-item {{ request()->routeIs('admin.bayi.profil*') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('admin.bayi.profil') }}">
                            <i class="mdi mdi-account menu-icon"></i>
                            <span class="menu-title">{{ __('messages.profile') }}</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </nav>
            
            <!-- Main Panel -->
            <div class="main-panel">
                <div class="content-wrapper">
                    <!-- Global Notifications -->
                    <div class="global-notifications-admin" style="position: fixed; top: 80px; right: 20px; z-index: 9999; max-width: 400px; width: 100%;">
                        @if(session('error'))
                            <div class="alert alert-danger" role="alert" style="display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 8px; background-color: #fee; border: 1px solid #fcc; color: #c33; margin-bottom: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: slideInRight 0.3s ease-out;">
                                <i class="mdi mdi-alert-circle" style="font-size: 20px;"></i>
                                <span style="flex: 1;">{{ session('error') }}</span>
                                <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: #c33; cursor: pointer; font-size: 18px; padding: 0; margin-left: 8px;">&times;</button>
                            </div>
                        @endif
                        @if(!session('error') && $errors->any())
                            <div class="alert alert-danger" role="alert" style="display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 8px; background-color: #fee; border: 1px solid #fcc; color: #c33; margin-bottom: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: slideInRight 0.3s ease-out;">
                                <i class="mdi mdi-alert-circle" style="font-size: 20px;"></i>
                                <span style="flex: 1;">{{ $errors->first() }}</span>
                                <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: #c33; cursor: pointer; font-size: 18px; padding: 0; margin-left: 8px;">&times;</button>
                            </div>
                        @endif
                        @if(session('success'))
                            <div class="alert alert-success" role="alert" style="display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 8px; background-color: #efe; border: 1px solid #cfc; color: #3c3; margin-bottom: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: slideInRight 0.3s ease-out;">
                                <i class="mdi mdi-check-circle" style="font-size: 20px;"></i>
                                <span style="flex: 1;">{{ session('success') }}</span>
                                <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: #3c3; cursor: pointer; font-size: 18px; padding: 0; margin-left: 8px;">&times;</button>
                            </div>
                        @endif
                        @if(session('warning'))
                            <div class="alert alert-warning" role="alert" style="display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 8px; background-color: #fffbeb; border: 1px solid #fde68a; color: #92400e; margin-bottom: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: slideInRight 0.3s ease-out;">
                                <i class="mdi mdi-alert" style="font-size: 20px;"></i>
                                <span style="flex: 1;">{{ session('warning') }}</span>
                                <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: #92400e; cursor: pointer; font-size: 18px; padding: 0; margin-left: 8px;">&times;</button>
                            </div>
                        @endif
                        @if(session('info'))
                            <div class="alert alert-info" role="alert" style="display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-radius: 8px; background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; margin-bottom: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); animation: slideInRight 0.3s ease-out;">
                                <i class="mdi mdi-information" style="font-size: 20px;"></i>
                                <span style="flex: 1;">{{ session('info') }}</span>
                                <button type="button" onclick="this.parentElement.remove()" style="background: none; border: none; color: #1e40af; cursor: pointer; font-size: 18px; padding: 0; margin-left: 8px;">&times;</button>
                            </div>
                        @endif
                    </div>

                    <style>
                        @keyframes slideInRight {
                            from {
                                transform: translateX(100%);
                                opacity: 0;
                            }
                            to {
                                transform: translateX(0);
                                opacity: 1;
                            }
                        }
                        @keyframes slideOutRight {
                            from {
                                transform: translateX(0);
                                opacity: 1;
                            }
                            to {
                                transform: translateX(100%);
                                opacity: 0;
                            }
                        }
                        @media (max-width: 768px) {
                            .global-notifications-admin {
                                top: 70px;
                                right: 10px;
                                left: 10px;
                                max-width: 100%;
                            }
                        }
                    </style>

                    <script>
                        // Bildirimleri otomatik kapat (5 saniye sonra)
                        document.addEventListener('DOMContentLoaded', function() {
                            const alerts = document.querySelectorAll('.global-notifications-admin .alert');
                            alerts.forEach(function(alert) {
                                setTimeout(function() {
                                    alert.style.animation = 'slideOutRight 0.3s ease-out';
                                    setTimeout(function() {
                                        alert.remove();
                                    }, 300);
                                }, 5000);
                            });
                        });
                    </script>
                    
                    @yield('content')
                </div>
                
                <!-- Footer -->
                <footer class="footer">
                    <div class="container-fluid clearfix">
                        <span class="text-muted d-block text-center text-sm-left d-sm-inline-block">
                            Copyright © {{ date('Y') }} DN Kreatif. Tüm hakları saklıdır.
                        </span>
                        <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">
                            Laravel {{ app()->version() }}
                        </span>
                    </div>
                </footer>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="{{ asset('yonetim/vendors/js/vendor.bundle.base.js') }}"></script>
    <script src="{{ asset('yonetim/vendors/js/vendor.bundle.addons.js') }}"></script>
    <script src="{{ asset('yonetim/js/off-canvas.js') }}"></script>
    <script src="{{ asset('yonetim/js/hoverable-collapse.js') }}"></script>
    <script src="{{ asset('yonetim/js/settings.js') }}"></script>
    <script src="{{ asset('yonetim/js/todolist.js') }}"></script>
    
    
    @stack('scripts')
    
    <!-- Admin Layout JavaScript -->
    <script src="{{ asset('yonetim/js/admin-layout.js') }}"></script>

    <!-- Sidebar: alt menü active highlight -->
    <script>
    (function () {
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;
        var here = (window.location.pathname || '').replace(/\/+$/, '') || '/';
        var matchedSubLink = null;
        sidebar.querySelectorAll('.sub-menu .nav-link[href]').forEach(function (a) {
            try {
                var u = new URL(a.href, window.location.origin);
                var p = (u.pathname || '').replace(/\/+$/, '') || '/';
                if (!p || p === '/' || p === '#') return;
                if (here === p || here.indexOf(p + '/') === 0) {
                    if (!matchedSubLink || p.length > matchedSubLink._matchLen) {
                        a._matchLen = p.length;
                        matchedSubLink = a;
                    }
                }
            } catch (e) {}
        });
        if (matchedSubLink) {
            matchedSubLink.classList.add('active');
            matchedSubLink._matchLen = undefined;
        }
    })();
    </script>

    <!-- Mobile Sidebar Toggle -->
    <script>
    (function () {
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;
        var togglers = document.querySelectorAll('[data-toggle="offcanvas"], [data-toggle="minimize"]');
        togglers.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                if (window.innerWidth <= 991) {
                    e.preventDefault();
                    sidebar.classList.toggle('active');
                }
            });
        });
        // Close on outside click (mobile only)
        document.addEventListener('click', function (e) {
            if (window.innerWidth > 991) return;
            if (!sidebar.classList.contains('active')) return;
            if (sidebar.contains(e.target)) return;
            if (e.target.closest('[data-toggle="offcanvas"], [data-toggle="minimize"]')) return;
            sidebar.classList.remove('active');
        });
        // Close on link click inside sidebar (mobile only)
        sidebar.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                if (window.innerWidth <= 991) {
                    setTimeout(function(){ sidebar.classList.remove('active'); }, 100);
                }
            });
        });
        // Reset on resize
        window.addEventListener('resize', function () {
            if (window.innerWidth > 991) sidebar.classList.remove('active');
        });
    })();
    </script>
</body>
</html>
