{{--
═══════════════════════════════════════════════════════════
BAYİ PANEL — ADMIN PANEL GÖRÜNÜMÜ (app-shell + admin-theme.css)
Admin _layout ile aynı kabuk; yalnızca sidebar bayi menüsü.
Tüm bayi alt sayfaları @section('panel_content') kullandığı için uyumludur.
═══════════════════════════════════════════════════════════
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Bayi Paneli') — İş Ortağım</title>

<link rel="icon" type="image/png" href="{{ asset('tema/uploads/favicon/favicon_dn.png') }}">
<meta name="theme-color" content="#b8b62e">

{{-- Admin tema (paylaşılan kabuk stili) --}}
<link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}?v={{ @filemtime(public_path('css/admin-theme.css')) ?: time() }}">
{{-- İkonlar --}}
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js" defer></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
{{-- Bayi alt sayfalarındaki Tailwind class'ları için --}}
<script src="https://cdn.tailwindcss.com"></script>

<style>
/* Bayi içeriğindeki dark-tema Tailwind class'larını açık temaya çevir */
#bayi-content-wrap{color:#1f2430;font-size:15px}
#bayi-content-wrap .glass{background:#fff;border:1px solid #ececec;box-shadow:0 2px 14px rgba(0,0,0,.05);border-radius:14px}
#bayi-content-wrap [class^="text-white"],#bayi-content-wrap [class*=" text-white"]{color:#1f2430 !important}
#bayi-content-wrap .text-white\/40{color:#9aa0a6 !important}
#bayi-content-wrap .text-white\/50{color:#888 !important}
#bayi-content-wrap .text-white\/60{color:#6c757d !important}
#bayi-content-wrap .text-white\/70{color:#5a6268 !important}
#bayi-content-wrap .text-yellow-400,#bayi-content-wrap .text-amber-400{color:#a36b00 !important}
#bayi-content-wrap .text-emerald-400{color:#059669 !important}
#bayi-content-wrap .gradient-text{background:linear-gradient(135deg,#b8b62e,#8a8a1f);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;color:transparent}
#bayi-content-wrap [class*="border-yellow-500"],#bayi-content-wrap [class*="border-white"]{border-color:#ececec !important}
#bayi-content-wrap .bg-black,#bayi-content-wrap .bg-neutral-900,#bayi-content-wrap .bg-neutral-800{background:#fff !important}
#bayi-content-wrap .stat-card{background:#fff !important;border:1px solid #f1f1f1 !important;border-radius:14px !important;padding:20px !important;box-shadow:0 2px 10px rgba(0,0,0,.04)}
#bayi-content-wrap .btn-y{background:linear-gradient(135deg,#b8b62e,#8a8a1f);color:#fff !important;padding:10px 18px;border-radius:10px;font-weight:600;display:inline-block;text-decoration:none;border:none;cursor:pointer;font-size:14px}
#bayi-content-wrap .btn-o{background:#fff;border:1px solid #d8d8d8;color:#444 !important;padding:10px 18px;border-radius:10px;font-weight:600;display:inline-block;text-decoration:none;cursor:pointer;font-size:14px}
#bayi-content-wrap .section-title{font-weight:700;font-size:1.05rem;margin-bottom:1rem;color:#1f2430;padding-bottom:.5rem;border-bottom:1px solid #f0f0f0}

/* Form elemanları — input/select/textarea kutuları (alt sayfalardaki formlar için ŞART) */
#bayi-content-wrap input[type=text],#bayi-content-wrap input[type=email],#bayi-content-wrap input[type=password],
#bayi-content-wrap input[type=number],#bayi-content-wrap input[type=tel],#bayi-content-wrap input[type=url],
#bayi-content-wrap input[type=date],#bayi-content-wrap input[type=datetime-local],#bayi-content-wrap input[type=time],
#bayi-content-wrap input[type=search],#bayi-content-wrap input:not([type]),#bayi-content-wrap select,#bayi-content-wrap textarea{
    background:#fff !important;color:#1f2430 !important;border:1px solid #d8d8d8 !important;border-radius:8px !important;
    padding:10px 14px !important;width:100%;font-size:14px;font-family:inherit;box-sizing:border-box;transition:all .15s;display:block;line-height:1.5;
}
#bayi-content-wrap input:focus,#bayi-content-wrap select:focus,#bayi-content-wrap textarea:focus{
    border-color:#b8b62e !important;outline:none;box-shadow:0 0 0 3px rgba(184,182,46,.15);
}
#bayi-content-wrap input::placeholder,#bayi-content-wrap textarea::placeholder{color:#aaa}
#bayi-content-wrap input[type=checkbox],#bayi-content-wrap input[type=radio]{width:auto !important;padding:0 !important;display:inline-block}
#bayi-content-wrap input:read-only,#bayi-content-wrap input:disabled{background:#f5f5f5 !important;color:#666 !important;cursor:not-allowed}
#bayi-content-wrap .field-label{display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;margin-bottom:6px;color:#444}
/* Tablolar + rozet (alt sayfa listeleri için) */
#bayi-content-wrap table{color:#1f2430;width:100%}
#bayi-content-wrap th{background:#faf9f0;color:#6b7280;font-weight:700;font-size:12px;text-transform:uppercase;letter-spacing:.5px;text-align:left;padding:10px 12px;border-bottom:1px solid #eee}
#bayi-content-wrap td{padding:10px 12px;border-bottom:1px solid #f3f3f3}
#bayi-content-wrap tbody tr:hover{background:#fafafa}
#bayi-content-wrap .badge{padding:3px 9px;border-radius:10px;font-size:.72rem;font-weight:600;display:inline-block}
#bayi-content-wrap .badge-danger{background:#ef4444;color:#fff}#bayi-content-wrap .badge-success{background:#22c55e;color:#fff}
#bayi-content-wrap .badge-warning{background:#f59e0b;color:#fff}#bayi-content-wrap .badge-info{background:#3b82f6;color:#fff}
/* Toggle switch (formlardaki aç/kapa) */
#bayi-content-wrap .toggle{position:relative;display:inline-block;width:46px;height:24px}
#bayi-content-wrap .toggle input{opacity:0;width:0;height:0}
#bayi-content-wrap .toggle .slider{position:absolute;cursor:pointer;inset:0;background:#ccc;border-radius:24px;transition:.2s}
#bayi-content-wrap .toggle .slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s}
#bayi-content-wrap .toggle input:checked+.slider{background:linear-gradient(135deg,#b8b62e,#8a8a1f)}
#bayi-content-wrap .toggle input:checked+.slider:before{transform:translateX(22px)}
.bayi-badge{background:#ef4444;color:#fff;font-size:10px;font-weight:700;min-width:18px;height:18px;border-radius:9px;padding:0 5px;display:inline-flex;align-items:center;justify-content:center;margin-left:auto}
</style>

@stack('head')
@stack('styles')
</head>

<body class="{{ request()->cookie('admin_theme', 'light') === 'dark' ? 'theme-dark' : '' }}">

@php
    $_kullaniciAdi = session('admin_adi', session('admin_kullanici_adi', 'Bayi'));
    $_baseHarf = strtoupper(mb_substr($_kullaniciAdi, 0, 1));
    $_okunmamisBildirim = 0;
    try {
        if (session('admin_id') && \Illuminate\Support\Facades\Schema::hasTable('bayi_bildirimler')) {
            $_okunmamisBildirim = \Illuminate\Support\Facades\DB::table('bayi_bildirimler')
                ->where('yonetici_id', session('admin_id'))->where('okundu', 0)->count();
        }
    } catch (\Throwable $e) {}
    if (!function_exists('bayiRoute')) {
        function bayiRoute($n) { return \Illuminate\Support\Facades\Route::has($n) ? route($n) : null; }
    }
@endphp

<div class="app-shell">

    {{-- ═══ BAYİ SIDEBAR (admin görünümü) ═══ --}}
    <aside id="appSidebar" class="app-sidebar">
        <div class="sidebar-brand">
            <div class="logo-mark">İO</div>
            <div class="logo-text">
                <strong>İş Ortağım</strong>
                <small>Bayi Paneli</small>
            </div>
        </div>

        <nav class="sidebar-nav">
            <a href="{{ route('admin.bayi.dashboard') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.dashboard') ? 'active' : '' }}">
                <i data-lucide="layout-dashboard"></i><span class="label">Dashboard</span>
            </a>

            <div class="sidebar-section">Satış & Müşteri</div>
            <a href="{{ route('admin.bayi.satislar') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.satislar') || request()->routeIs('admin.bayi.satis.*') ? 'active' : '' }}">
                <i data-lucide="shopping-cart"></i><span class="label">Satışlarım</span>
            </a>
            <a href="{{ route('admin.bayi.musteriler') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.musteriler') || request()->routeIs('admin.bayi.musteri.*') ? 'active' : '' }}">
                <i data-lucide="users"></i><span class="label">Müşterilerim</span>
            </a>

            <div class="sidebar-section">Finans</div>
            <a href="{{ route('admin.bayi.kazanclar') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.kazanclar') ? 'active' : '' }}">
                <i data-lucide="coins"></i><span class="label">Kazançlarım</span>
            </a>
            <a href="{{ route('admin.bayi.odeme') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.odeme*') || request()->routeIs('admin.bayi.banka.*') ? 'active' : '' }}">
                <i data-lucide="wallet"></i><span class="label">Ödemeler</span>
            </a>

            <div class="sidebar-section">Büyüme</div>
            @if(bayiRoute('admin.bayi.referans.link'))
            <a href="{{ route('admin.bayi.referans.link') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.referans.*') || request()->routeIs('admin.bayi.alt.*') ? 'active' : '' }}">
                <i data-lucide="share-2"></i><span class="label">Referans Sistemi</span>
            </a>
            @endif
            @if(bayiRoute('admin.bayi.promosyon.kodlar'))
            <a href="{{ route('admin.bayi.promosyon.kodlar') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.promosyon.*') || request()->routeIs('admin.bayi.kampanyalar') ? 'active' : '' }}">
                <i data-lucide="megaphone"></i><span class="label">Pazarlama</span>
            </a>
            @endif
            @if(bayiRoute('admin.bayi.raporlar'))
            <a href="{{ route('admin.bayi.raporlar') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.raporlar') || request()->routeIs('admin.bayi.rapor.*') ? 'active' : '' }}">
                <i data-lucide="line-chart"></i><span class="label">Raporlar</span>
            </a>
            @endif

            <div class="sidebar-section">Hesap</div>
            @if(bayiRoute('admin.bayi.destek.tickets'))
            <a href="{{ route('admin.bayi.destek.tickets') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.destek.*') || request()->routeIs('admin.bayi.sss') ? 'active' : '' }}">
                <i data-lucide="life-buoy"></i><span class="label">Destek</span>
            </a>
            @endif
            @if(bayiRoute('admin.bayi.bildirimler'))
            <a href="{{ route('admin.bayi.bildirimler') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.bildirimler') ? 'active' : '' }}">
                <i data-lucide="bell"></i><span class="label">Bildirimler</span>
                @if($_okunmamisBildirim > 0)<span class="bayi-badge">{{ $_okunmamisBildirim }}</span>@endif
            </a>
            @endif
            <a href="{{ route('admin.bayi.profil') }}" class="sidebar-link {{ request()->routeIs('admin.bayi.profil') || request()->routeIs('admin.bayi.guvenlik') ? 'active' : '' }}">
                <i data-lucide="user-cog"></i><span class="label">Profil & Ayarlar</span>
            </a>
            @if(bayiRoute('hesabim'))
            <a href="{{ route('hesabim') }}" class="sidebar-link" style="color:#4f46e5">
                <i data-lucide="home"></i><span class="label">Müşteri Paneli</span>
            </a>
            @endif
        </nav>
    </aside>
    <div id="sidebarBackdrop" class="sidebar-backdrop" onclick="closeSidebar()"></div>

    <div class="app-main">

        <header class="app-header">
            <div class="header-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Menü"><i data-lucide="menu"></i></button>
                <span style="font-weight:700;color:var(--ink,#1f2430)">@yield('page_title', 'Bayi Paneli')</span>
            </div>
            <div class="header-right">
                @if(bayiRoute('admin.bayi.bildirimler'))
                <a href="{{ route('admin.bayi.bildirimler') }}" class="icon-btn" title="Bildirimler">
                    <i data-lucide="bell"></i>@if($_okunmamisBildirim > 0)<span class="badge-dot"></span>@endif
                </a>
                @endif
                <a href="{{ url('/') }}" target="_blank" rel="noopener" class="icon-btn" title="Siteyi aç"><i data-lucide="external-link"></i></a>
                <button class="icon-btn" onclick="toggleTheme()" title="Tema"><i data-lucide="moon" id="themeIconMoon"></i><i data-lucide="sun" id="themeIconSun" style="display:none"></i></button>
                <div class="user-menu">
                    <button class="user-trigger" onclick="toggleUserMenu(event)">
                        <div class="user-avatar">{{ $_baseHarf }}</div>
                        <div class="user-meta"><strong>{{ $_kullaniciAdi }}</strong><small>Bayi</small></div>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        <a href="{{ route('admin.bayi.profil') }}"><i data-lucide="user"></i><span>Profilim</span></a>
                        @if(bayiRoute('admin.bayi.guvenlik'))<a href="{{ route('admin.bayi.guvenlik') }}"><i data-lucide="lock"></i><span>Güvenlik</span></a>@endif
                        <div class="divider"></div>
                        <a href="{{ route('admin.cikis') }}" style="color:var(--danger,#dc3545)"><i data-lucide="log-out"></i><span>Çıkış Yap</span></a>
                    </div>
                </div>
            </div>
        </header>

        <main class="app-content">
            @if(session('success'))<div class="alert alert-success"><i data-lucide="check-circle"></i><div>{{ session('success') }}</div></div>@endif
            @if(session('error'))<div class="alert alert-danger"><i data-lucide="alert-circle"></i><div>{{ session('error') }}</div></div>@endif
            @if(session('info'))<div class="alert alert-info"><i data-lucide="info"></i><div>{{ session('info') }}</div></div>@endif
            @if($errors->any())<div class="alert alert-danger"><i data-lucide="alert-triangle"></i><div><strong>Hatalar:</strong><ul style="margin:6px 0 0 18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div></div>@endif

            @yield('panel_top')
            <div id="bayi-content-wrap">
                @yield('panel_content')
            </div>
        </main>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => { if (window.lucide) window.lucide.createIcons(); updateThemeIcon(); });
    function toggleSidebar(){ document.getElementById('appSidebar')?.classList.toggle('open'); document.getElementById('sidebarBackdrop')?.classList.toggle('show'); }
    function closeSidebar(){ document.getElementById('appSidebar')?.classList.remove('open'); document.getElementById('sidebarBackdrop')?.classList.remove('show'); }
    function toggleTheme(){ document.body.classList.toggle('theme-dark'); const d=document.body.classList.contains('theme-dark'); localStorage.setItem('admin_theme', d?'dark':'light'); document.cookie='admin_theme='+(d?'dark':'light')+';path=/;max-age=31536000;SameSite=Lax'; updateThemeIcon(); }
    function updateThemeIcon(){ const d=document.body.classList.contains('theme-dark'); const m=document.getElementById('themeIconMoon'),s=document.getElementById('themeIconSun'); if(m)m.style.display=d?'none':'block'; if(s)s.style.display=d?'block':'none'; }
    if (localStorage.getItem('admin_theme')==='dark') document.body.classList.add('theme-dark');
    function toggleUserMenu(e){ e.stopPropagation(); document.getElementById('userDropdown')?.classList.toggle('show'); }
    document.addEventListener('click', e => { if(!e.target.closest('.user-menu')) document.getElementById('userDropdown')?.classList.remove('show'); });
</script>

@stack('scripts')
</body>
</html>
