{{-- 
═══════════════════════════════════════════════════════════
DN KREATİF — İŞ ORTAĞIM ADMIN PANEL — MASTER LAYOUT v2.2
+ Favicon entegrasyonu
+ Route::has() koruması
+ Sidebar partial
═══════════════════════════════════════════════════════════
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Admin') — İş Ortağım</title>

{{-- ═══ FAVICON ═══ --}}
<link rel="icon" type="image/svg+xml" href="{{ asset('tema/uploads/favicon/favicon_io.svg') }}?v=io">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('tema/uploads/favicon/favicon_io.png') }}?v=io">
<link rel="icon" type="image/png" sizes="16x16" href="{{ asset('tema/uploads/favicon/favicon_io.png') }}?v=io">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('tema/uploads/favicon/favicon_io_180.png') }}?v=io">
<link rel="shortcut icon" href="{{ asset('tema/uploads/favicon/favicon_io.ico') }}?v=io">
<meta name="theme-color" content="#b8b62e">

<link rel="stylesheet" href="{{ asset('css/admin-theme.css') }}?v={{ @filemtime(public_path('css/admin-theme.css')) ?: time() }}">

{{-- Lucide Icons (yerel — CDN'i Tracking Prevention engelliyordu) --}}
<script src="{{ asset('vendor/lucide/lucide.min.js') }}" defer></script>

@stack('head')
</head>

<body class="{{ request()->cookie('admin_theme', 'light') === 'dark' ? 'theme-dark' : '' }}">

{{-- ═══ AÇILIŞ (LOADING) EKRANI ═══ --}}
<style>
    #ioLoader{position:fixed;inset:0;z-index:99999;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:18px;
        background:#ffffff;transition:opacity .45s ease, visibility .45s ease}
    body.theme-dark #ioLoader{background:#0f0f0f}
    #ioLoader.io-hide{opacity:0;visibility:hidden}
    #ioLoader .io-badge{width:74px;height:74px;border-radius:20px;background:#b8b62e;color:#fff;display:flex;align-items:center;justify-content:center;
        font-size:33px;font-weight:800;letter-spacing:-1.5px;box-shadow:0 12px 34px rgba(184,182,46,.38);animation:ioPulse 1.4s ease-in-out infinite}
    #ioLoader .io-name{font-size:18px;font-weight:700;color:#1a1d24;letter-spacing:.4px}
    body.theme-dark #ioLoader .io-name{color:#e9e9e0}
    #ioLoader .io-dots{display:flex;gap:7px}
    #ioLoader .io-dots span{width:8px;height:8px;border-radius:50%;background:#b8b62e;opacity:.3;animation:ioDot 1.2s ease-in-out infinite}
    #ioLoader .io-dots span:nth-child(2){animation-delay:.18s}
    #ioLoader .io-dots span:nth-child(3){animation-delay:.36s}
    @keyframes ioPulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(.92);opacity:.85}}
    @keyframes ioDot{0%,100%{opacity:.25;transform:translateY(0)}50%{opacity:1;transform:translateY(-5px)}}
    @media (prefers-reduced-motion: reduce){#ioLoader .io-badge,#ioLoader .io-dots span{animation:none}}
</style>
<div id="ioLoader" aria-hidden="true">
    <div class="io-badge">io</div>
    <div class="io-name">İş Ortağım</div>
    <div class="io-dots"><span></span><span></span><span></span></div>
</div>
<script>
(function(){
    var el = document.getElementById('ioLoader');
    if (!el) return;
    var t = null;
    function hide(){ el.classList.add('io-hide'); clearTimeout(t); setTimeout(function(){ el.style.display = 'none'; }, 480); }
    function show(){ clearTimeout(t); el.style.display = 'flex'; el.classList.remove('io-hide'); t = setTimeout(hide, 8000); }
    // Sayfa hazır olunca gizle (load + bfcache pageshow)
    if (document.readyState === 'complete') hide();
    else window.addEventListener('load', hide);
    window.addEventListener('pageshow', function(e){ if (e.persisted) hide(); });
    // Takılırsa en geç 8 sn sonra kapan
    t = setTimeout(hide, 8000);
    // İç sayfa geçişlerinde tekrar göster (Duty hissi)
    document.addEventListener('click', function(e){
        var a = e.target.closest('a');
        if (!a) return;
        if (a.target === '_blank' || a.hasAttribute('download') || e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return;
        var href = a.getAttribute('href') || '';
        if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0) return;
        var sameSite = href.charAt(0) === '/' || href.indexOf(location.host) !== -1;
        if (sameSite) show();
    }, true);
})();
</script>

@php
    $_kullaniciAdi = session('admin_adi', session('admin_kullanici_adi', 'Admin'));
    $_kullaniciRol = 'Yönetici';
    try {
        $_rolKaydi = \Illuminate\Support\Facades\DB::table('roller')
            ->where('id', (int) session('admin_rol', 0))->first();
        if ($_rolKaydi) {
            $_kullaniciRol = $_rolKaydi->ad;
        }
    } catch (\Throwable $e) {}
    $_baseHarf = strtoupper(mb_substr($_kullaniciAdi, 0, 1));

    // Profil fotoğrafı (kolon ve dosya varsa)
    $_profilFoto = null;
    try {
        if (session('admin_id') && \Illuminate\Support\Facades\Schema::hasColumn('yoneticiler', 'profil_foto')) {
            $_pf = \Illuminate\Support\Facades\DB::table('yoneticiler')
                ->where('id', session('admin_id'))->value('profil_foto');
            if ($_pf && is_file(public_path($_pf))) {
                $_profilFoto = asset($_pf);
            }
        }
    } catch (\Throwable $e) {}

    try {
        // NOT: admin_bildirimler ortak tablo; yonetici_id kolonu varsa
        // sayim endpoint'iyle AYNI filtre uygulanir (kisisel + genel NULL).
        $_bildirimQ = \Illuminate\Support\Facades\DB::table('admin_bildirimler')
            ->where('okundu', 0);
        if (\Illuminate\Support\Facades\Schema::hasColumn('admin_bildirimler', 'yonetici_id')) {
            $_aktifAdminId = session('admin_id');
            $_bildirimQ->where(function ($w) use ($_aktifAdminId) {
                $w->where('yonetici_id', $_aktifAdminId)
                  ->orWhereNull('yonetici_id');
            });
        }
        $_okunmamisBildirim = $_bildirimQ->count();
    } catch (\Throwable $e) { $_okunmamisBildirim = 0; }
@endphp

<div class="app-shell">

    @include('admin._partials.sidebar')

    <div class="app-main">

        <header class="app-header">
            <div class="header-left">
                <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Menüyü aç/kapat">
                    <i data-lucide="menu"></i>
                </button>
            </div>

            <div class="header-search">
                <i data-lucide="search" class="search-ic"></i>
                <input type="text" id="navSearch" autocomplete="off"
                       placeholder="Menüde ara... (sayfa adı yazın)"
                       onkeyup="navSearchHandler(event)" onfocus="navSearchHandler(event)">
                <div class="header-search-results" id="navSearchResults"></div>
            </div>

            <div class="header-right">

                @if(\Illuminate\Support\Facades\Route::has('admin.bildirimler.index'))
                <div class="notif-wrap" style="position:relative">
                    <button type="button" class="icon-btn" title="Bildirimler" onclick="toggleNotifDropdown(event)">
                        <i data-lucide="bell"></i>
                        <span class="notif-badge" id="notifBadge" style="display:{{ $_okunmamisBildirim > 0 ? 'flex' : 'none' }}">{{ $_okunmamisBildirim > 9 ? '9+' : $_okunmamisBildirim }}</span>
                    </button>
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-dd-head">
                            <strong>Bildirimler</strong>
                            <span class="notif-dd-actions">
                                <button type="button" id="notifHepsiniOku" title="Tüm bildirimleri okundu işaretle">Tümünü oku</button>
                                <a href="{{ route('admin.bildirimler.index') }}">Tümü</a>
                            </span>
                        </div>
                        <div class="notif-dd-list" id="notifDdList">
                            <div class="notif-dd-empty">Yükleniyor…</div>
                        </div>
                    </div>
                </div>
                <style>
                    .notif-badge{position:absolute;top:-4px;right:-4px;min-width:17px;height:17px;padding:0 4px;border-radius:9px;background:#ef4444;color:#fff;font-size:10px;font-weight:700;align-items:center;justify-content:center;line-height:1;box-shadow:0 0 0 2px var(--surface,#fff)}
                    .notif-dropdown{position:absolute;top:46px;right:0;width:340px;max-width:90vw;background:var(--surface,#fff);border:1px solid var(--border,#e5e7eb);border-radius:14px;box-shadow:0 16px 44px rgba(0,0,0,.16);overflow:hidden;display:none;z-index:1000}
                    .notif-dropdown.show{display:block}
                    .notif-dd-head{display:flex;align-items:center;justify-content:space-between;padding:13px 16px;border-bottom:1px solid var(--border,#eee)}
                    .notif-dd-head strong{font-size:14px}
                    .notif-dd-head a{font-size:12px;color:var(--brand,#b8b62e);text-decoration:none;font-weight:600}
                    .notif-dd-actions{display:flex;align-items:center;gap:12px}
                    .notif-dd-actions button{font-size:12px;color:var(--brand,#b8b62e);font-weight:600;background:none;border:0;padding:0;cursor:pointer;font-family:inherit}
                    .notif-dd-actions button:hover{text-decoration:underline}
                    .notif-dd-actions button:disabled{opacity:.5;cursor:default}
                    .notif-dd-list{max-height:360px;overflow-y:auto}
                    .notif-dd-item{display:block;padding:12px 16px;border-bottom:1px solid var(--border,#f0f0ec);text-decoration:none;color:inherit;transition:background .15s}
                    .notif-dd-item:hover{background:var(--bg-subtle,#f7f7f4)}
                    .notif-dd-item.unread{background:rgba(184,182,46,.05)}
                    .notif-dd-item .t{font-size:13px;font-weight:700;color:var(--text,#1f2419);margin-bottom:2px}
                    .notif-dd-item .m{font-size:12px;color:var(--text-secondary,#64748b);line-height:1.4}
                    .notif-dd-item .z{font-size:11px;color:var(--text-muted,#94a3b8);margin-top:3px}
                    .notif-dd-empty{padding:28px 16px;text-align:center;color:var(--text-muted,#94a3b8);font-size:13px}
                    /* Mobil: bildirim kutusu ekran dışına taşmasın, tam genişlik */
                    @media (max-width:560px){
                        .notif-dropdown{position:fixed;top:54px;left:8px;right:8px;width:auto;max-width:none}
                        .notif-dd-list{max-height:72vh}
                    }
                </style>
                @endif

                {{-- SİTEYİ GÖSTER --}}
                <a href="{{ site_adresi() }}" target="_blank" rel="noopener"
                   class="icon-btn" title="Siteyi yeni sekmede aç">
                    <i data-lucide="external-link"></i>
                </a>

                <button class="icon-btn" onclick="toggleTheme()" title="Tema değiştir">
                    <i data-lucide="moon" id="themeIconMoon"></i>
                    <i data-lucide="sun" id="themeIconSun" style="display:none"></i>
                </button>

                <div class="user-menu">
                    <button class="user-trigger" onclick="toggleUserMenu(event)">
                        <div class="user-avatar" style="overflow:hidden">@if($_profilFoto)<img src="{{ $_profilFoto }}" alt="" style="width:100%;height:100%;object-fit:cover">@else{{ $_baseHarf }}@endif</div>
                        <div class="user-meta">
                            <strong>{{ $_kullaniciAdi }}</strong>
                            <small>{{ $_kullaniciRol }}</small>
                        </div>
                    </button>
                    <div class="user-dropdown" id="userDropdown">
                        @if(\Illuminate\Support\Facades\Route::has('admin.profil'))
                        <a href="{{ route('admin.profil') }}">
                            <i data-lucide="user"></i>
                            <span>Profilim</span>
                        </a>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('admin.guvenlik'))
                        <a href="{{ route('admin.guvenlik') }}">
                            <i data-lucide="lock"></i>
                            <span>Güvenlik</span>
                        </a>
                        @endif
                        @if(\Illuminate\Support\Facades\Route::has('admin.ayarlar.index'))
                        <a href="{{ route('admin.ayarlar.index') }}">
                            <i data-lucide="settings"></i>
                            <span>Ayarlar</span>
                        </a>
                        @endif
                        <div class="divider"></div>
                        @if(\Illuminate\Support\Facades\Route::has('admin.cikis'))
                        <a href="{{ route('admin.cikis') }}" style="color: var(--danger)">
                            <i data-lucide="log-out"></i>
                            <span>Çıkış Yap</span>
                        </a>
                        @endif
                    </div>
                </div>

            </div>
        </header>

        <main class="app-content">

            {{-- Flash mesajları — sağ üstte yüzen toast (çıkış ekranı stili) --}}
            <style>
                .io-toasts{position:fixed;top:82px;right:22px;z-index:9000;display:flex;flex-direction:column;gap:12px;max-width:92vw;pointer-events:none}
                .io-toast{pointer-events:auto;display:flex;align-items:flex-start;gap:12px;width:360px;max-width:92vw;background:#fff;border:1px solid #eceae0;
                    border-radius:14px;padding:13px 13px 13px 15px;box-shadow:0 14px 40px rgba(20,20,20,.16);animation:ioToastIn .35s cubic-bezier(.2,.8,.25,1)}
                body.theme-dark .io-toast{background:#1b1b1b;border-color:#2c2c2c;box-shadow:0 14px 40px rgba(0,0,0,.5)}
                .io-toast.io-out{animation:ioToastOut .35s ease forwards}
                .io-toast-ic{flex:0 0 auto;width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center}
                .io-toast-ic i{width:20px;height:20px}
                .io-toast-success .io-toast-ic{background:#e7f6ee;color:#16a34a}
                .io-toast-error .io-toast-ic{background:#fde8e8;color:#dc2626}
                .io-toast-info .io-toast-ic{background:#e6f0fb;color:#2563eb}
                .io-toast-msg{flex:1 1 auto;font-size:14.5px;line-height:1.42;color:#1f2430;padding-top:6px}
                body.theme-dark .io-toast-msg{color:#e8e8e0}
                .io-toast-msg ul{font-size:13.5px;margin:6px 0 0 16px;padding:0}
                .io-toast-x{flex:0 0 auto;background:none;border:0;cursor:pointer;color:#9aa0ac;padding:6px;border-radius:8px;line-height:0;transition:background .15s,color .15s}
                .io-toast-x:hover{background:rgba(0,0,0,.06);color:#4b5563}
                body.theme-dark .io-toast-x:hover{background:rgba(255,255,255,.08);color:#cfd2d8}
                .io-toast-x i{width:18px;height:18px}
                @keyframes ioToastIn{from{opacity:0;transform:translateX(32px)}to{opacity:1;transform:translateX(0)}}
                @keyframes ioToastOut{to{opacity:0;transform:translateX(32px)}}
                @media (max-width:560px){.io-toasts{top:70px;right:10px;left:10px}.io-toast{width:auto}}
            </style>
            @if(session('success') || session('error') || session('info') || $errors->any())
            <div class="io-toasts" id="ioToasts">
                @if(session('success'))
                    <div class="io-toast io-toast-success" role="status">
                        <span class="io-toast-ic"><i data-lucide="check-circle"></i></span>
                        <div class="io-toast-msg">{{ session('success') }}</div>
                        <button type="button" class="io-toast-x" onclick="var t=this.closest('.io-toast');t.classList.add('io-out');setTimeout(function(){t.remove()},360)" aria-label="Kapat"><i data-lucide="x"></i></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="io-toast io-toast-error" role="alert">
                        <span class="io-toast-ic"><i data-lucide="alert-circle"></i></span>
                        <div class="io-toast-msg">{{ session('error') }}</div>
                        <button type="button" class="io-toast-x" onclick="var t=this.closest('.io-toast');t.classList.add('io-out');setTimeout(function(){t.remove()},360)" aria-label="Kapat"><i data-lucide="x"></i></button>
                    </div>
                @endif
                @if(session('info'))
                    <div class="io-toast io-toast-info" role="status">
                        <span class="io-toast-ic"><i data-lucide="info"></i></span>
                        <div class="io-toast-msg">{{ session('info') }}</div>
                        <button type="button" class="io-toast-x" onclick="var t=this.closest('.io-toast');t.classList.add('io-out');setTimeout(function(){t.remove()},360)" aria-label="Kapat"><i data-lucide="x"></i></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="io-toast io-toast-error" role="alert">
                        <span class="io-toast-ic"><i data-lucide="alert-triangle"></i></span>
                        <div class="io-toast-msg">
                            <strong>Lütfen formdaki hataları düzeltin:</strong>
                            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        </div>
                        <button type="button" class="io-toast-x" onclick="var t=this.closest('.io-toast');t.classList.add('io-out');setTimeout(function(){t.remove()},360)" aria-label="Kapat"><i data-lucide="x"></i></button>
                    </div>
                @endif
            </div>
            @endif
            <script>
            (function(){
                var box = document.getElementById('ioToasts');
                if (!box) return;
                if (window.lucide) lucide.createIcons();
                var list = box.querySelectorAll('.io-toast');
                for (var i = 0; i < list.length; i++) {
                    (function(el, idx){
                        var sure = el.classList.contains('io-toast-error') ? 7000 : 5000;
                        setTimeout(function(){
                            el.classList.add('io-out');
                            setTimeout(function(){ if (el.parentNode) el.parentNode.removeChild(el); }, 360);
                        }, sure + idx * 150);
                    })(list[i], i);
                }
            })();
            </script>

            @yield('content')

        </main>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) window.lucide.createIcons();
        updateThemeIcon();
        try {
            const open = JSON.parse(localStorage.getItem('sidebar_open_groups') || '[]');
            open.forEach(key => {
                const el = document.querySelector('[data-group="' + key + '"]');
                if (el) el.classList.add('open');
            });
        } catch (e) {}
    });

    function toggleSidebar() {
        // Mobilde (dar ekran) sidebar'i ac/kapat; masaustunde daralt/genislet
        if (window.matchMedia('(max-width: 768px)').matches) {
            document.getElementById('appSidebar')?.classList.toggle('open');
            document.getElementById('sidebarBackdrop')?.classList.toggle('show');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar_collapsed',
                document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
        }
    }

    function closeSidebar() {
        document.getElementById('appSidebar')?.classList.remove('open');
        document.getElementById('sidebarBackdrop')?.classList.remove('show');
    }

    // CRM tab'larindaki "Yeni Ekle" formlarini ac/kapat
    function crmToggleForm(id) {
        var el = document.getElementById(id);
        if (!el) return;
        var gizli = (el.style.display === 'none' || el.style.display === '');
        el.style.display = gizli ? 'block' : 'none';
        if (gizli) {
            if (window.lucide) window.lucide.createIcons();
            el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    // Masaustu daralt tercihini sayfa yuklenince uygula
    if (localStorage.getItem('sidebar_collapsed') === '1'
        && !window.matchMedia('(max-width: 768px)').matches) {
        document.body.classList.add('sidebar-collapsed');
    }

    // ─── MENÜ ARAMA: sidebar linklerini tara, eslesenleri goster, sec -> git ───
    let _navItems = null;
    function _collectNavItems() {
        if (_navItems) return _navItems;
        _navItems = [];
        document.querySelectorAll('#appSidebar a.sidebar-link').forEach(function(a) {
            const label = (a.querySelector('.label')?.textContent || a.textContent || '').trim();
            const href = a.getAttribute('href');
            if (label && href && href !== '#') {
                _navItems.push({ label: label, href: href });
            }
        });
        return _navItems;
    }
    function navSearchHandler(e) {
        const box = document.getElementById('navSearchResults');
        const q = (e.target.value || '').trim().toLocaleLowerCase('tr');
        if (!box) return;
        if (q.length < 1) { box.classList.remove('show'); box.innerHTML = ''; return; }
        const items = _collectNavItems();
        const matches = items.filter(it => it.label.toLocaleLowerCase('tr').includes(q)).slice(0, 12);
        if (matches.length === 0) {
            box.innerHTML = '<div class="no-result">Eşleşen sayfa yok</div>';
        } else {
            box.innerHTML = matches.map(m =>
                '<a href="' + m.href + '">' + m.label + '</a>'
            ).join('');
        }
        box.classList.add('show');
        // Enter -> ilk sonuca git
        if (e.key === 'Enter' && matches.length > 0) {
            window.location = matches[0].href;
        }
    }
    // Disari tiklayinca arama sonuclarini kapat
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header-search')) {
            document.getElementById('navSearchResults')?.classList.remove('show');
        }
    });

    function toggleGroup(key) {
        const el = document.querySelector('[data-group="' + key + '"]');
        if (!el) {
            console.warn('toggleGroup: data-group="' + key + '" bulunamadı');
            return;
        }
        el.classList.toggle('open');
        const allOpen = Array.from(document.querySelectorAll('.sidebar-group.open')).map(g => g.dataset.group);
        localStorage.setItem('sidebar_open_groups', JSON.stringify(allOpen));
    }

    // Sidebar dropdown butonlarına click bağla (sayfa yüklendikten sonra)
    function _bindSidebarToggles() {
        document.querySelectorAll('[data-toggle-group]').forEach(function(btn) {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                toggleGroup(btn.dataset.toggleGroup);
            });
        });
    }
    _bindSidebarToggles();
    document.addEventListener('DOMContentLoaded', _bindSidebarToggles);

    function toggleTheme() {
        document.body.classList.toggle('theme-dark');
        const isDark = document.body.classList.contains('theme-dark');
        localStorage.setItem('admin_theme', isDark ? 'dark' : 'light');
        document.cookie = 'admin_theme=' + (isDark ? 'dark' : 'light') + ';path=/;max-age=31536000;SameSite=Lax';
        updateThemeIcon();
    }

    function updateThemeIcon() {
        const isDark = document.body.classList.contains('theme-dark');
        const moon = document.getElementById('themeIconMoon');
        const sun = document.getElementById('themeIconSun');
        if (moon) moon.style.display = isDark ? 'none' : 'block';
        if (sun) sun.style.display = isDark ? 'block' : 'none';
    }

    if (localStorage.getItem('admin_theme') === 'dark') {
        document.body.classList.add('theme-dark');
    }

    function toggleUserMenu(e) {
        e.stopPropagation();
        document.getElementById('userDropdown')?.classList.toggle('show');
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-menu')) {
            document.getElementById('userDropdown')?.classList.remove('show');
        }
    });

    // ─── BİLDİRİM POLLING (sayfa yenilemeden) ───
    (function() {
        const SAYIM_URL = "{{ route('admin.bildirimler.sayim') }}";
        const OKUNDU_URL_T = "{{ url('admin/bildirimler') }}/__ID__/okundu";
        const HEPSINI_OKU_URL = "{{ route('admin.bildirimler.hepsini-oku') }}";
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const badge = document.getElementById('notifBadge');
        const ddList = document.getElementById('notifDdList');
        const dd = document.getElementById('notifDropdown');
        if (!badge) return;

        let sonId = 0;        // en yeni bildirim id'si (yeni geleni anlamak icin)
        let ilkYukleme = true;
        let okunmamisSayisi = {{ (int) $_okunmamisBildirim }};

        function esc(s){ const d=document.createElement('div'); d.textContent=s==null?'':s; return d.innerHTML; }

        // Rozeti tek yerden guncelle (tiklayinca aninda dussun diye)
        function rozetGuncelle(n) {
            okunmamisSayisi = Math.max(0, n);
            if (okunmamisSayisi > 0) {
                badge.style.display = 'flex';
                badge.textContent = okunmamisSayisi > 9 ? '9+' : okunmamisSayisi;
            } else {
                badge.style.display = 'none';
            }
        }

        // Tek bildirimi sunucuda okundu isaretle (keepalive: sayfadan ayrilsak bile gider)
        function okunduPost(id) {
            const fd = new FormData();
            fd.append('_token', CSRF);
            return fetch(OKUNDU_URL_T.replace('__ID__', id), {
                method: 'POST',
                body: fd,
                headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                credentials: 'same-origin',
                keepalive: true
            }).catch(function(){});
        }

        function render(data) {
            rozetGuncelle(data.okunmamis || 0);

            // dropdown listesi
            const list = data.bildirimler || [];
            if (list.length === 0) {
                ddList.innerHTML = '<div class="notif-dd-empty">Henüz bildirim yok</div>';
            } else {
                ddList.innerHTML = list.map(function(b){
                    const cls = b.okundu ? '' : 'unread';
                    const attrs = ' data-id="'+b.id+'" data-okundu="'+(b.okundu ? 1 : 0)+'"';
                    const inner = '<div class="t">'+esc(b.baslik)+'</div>'
                                + (b.mesaj ? '<div class="m">'+esc(b.mesaj)+'</div>' : '')
                                + (b.zaman ? '<div class="z">⏱ '+esc(b.zaman)+'</div>' : '');
                    return b.link
                        ? '<a class="notif-dd-item '+cls+'"'+attrs+' href="'+b.link+'">'+inner+'</a>'
                        : '<div class="notif-dd-item '+cls+'"'+attrs+'>'+inner+'</div>';
                }).join('');
            }

            // yeni bildirim geldi mi? (ilk yuklemede sessiz)
            const yeniId = list.length ? list[0].id : 0;
            if (!ilkYukleme && yeniId > sonId && document.getElementById('notifBadge')) {
                badge.animate(
                    [{transform:'scale(1)'},{transform:'scale(1.4)'},{transform:'scale(1)'}],
                    {duration:400}
                );
            }
            if (yeniId > sonId) sonId = yeniId;
            ilkYukleme = false;
        }

        function cek() {
            if (document.hidden) return;  // arka plandayken cekme
            fetch(SAYIM_URL, {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin'})
                .then(r => r.ok ? r.json() : null)
                .then(d => { if (d) render(d); })
                .catch(()=>{});
        }

        cek();                          // ilk cekim
        let timer = setInterval(cek, 15000);  // her 15 sn
        // sekme tekrar gorununce hemen tazele
        document.addEventListener('visibilitychange', function(){ if (!document.hidden) cek(); });

        window.toggleNotifDropdown = function(e){
            e.stopPropagation();
            dd.classList.toggle('show');
            if (dd.classList.contains('show')) cek();
        };
        document.addEventListener('click', function(e){
            if (!e.target.closest('.notif-wrap')) dd.classList.remove('show');
        });

        // ─── Bildirime tiklayinca: okundu isaretle, rozeti dusur, SONRA linke git ───
        ddList.addEventListener('click', function(e){
            const item = e.target.closest('.notif-dd-item');
            if (!item) return;
            const id = item.dataset.id;
            const okunmamisMi = item.dataset.okundu === '0';
            const href = (item.tagName === 'A') ? item.getAttribute('href') : null;

            if (!id || !okunmamisMi) return; // zaten okunmus -> link varsayilan sekilde calisir

            // Aninda gorsel guncelle
            item.classList.remove('unread');
            item.dataset.okundu = '1';
            rozetGuncelle(okunmamisSayisi - 1);

            const istek = okunduPost(id);

            if (href) {
                // Linki bekletip POST bittikten sonra git (rozet yeni sayfada da dogru gelsin)
                e.preventDefault();
                let gidildi = false;
                const git = function(){ if (!gidildi) { gidildi = true; window.location = href; } };
                istek.then(git, git);
                setTimeout(git, 800); // sunucu gecikirse en gec 0.8 sn sonra yine git
            }
        });

        // ─── "Tümünü oku" butonu ───
        const btnHepsi = document.getElementById('notifHepsiniOku');
        if (btnHepsi) {
            btnHepsi.addEventListener('click', function(e){
                e.stopPropagation();
                btnHepsi.disabled = true;
                const fd = new FormData();
                fd.append('_token', CSRF);
                fetch(HEPSINI_OKU_URL, {
                    method: 'POST',
                    body: fd,
                    headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
                    credentials: 'same-origin'
                })
                .then(function(){
                    rozetGuncelle(0);
                    ddList.querySelectorAll('.notif-dd-item.unread').forEach(function(el){
                        el.classList.remove('unread');
                        el.dataset.okundu = '1';
                    });
                })
                .catch(function(){})
                .finally(function(){ btnHepsi.disabled = false; cek(); });
            });
        }
    })();
</script>

@stack('scripts')

@if(Route::has('admin.dm.index') && !in_array((int)session('admin_rol'), [3]) && !request()->routeIs('admin.dm.*'))
    @include('admin._partials.dm-widget')
@endif

@if(Route::has('admin.presence.ping'))
<script>
(function(){
    var PING = @json(route('admin.presence.ping'));
    var CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var sonEtkilesim = Date.now();
    ['mousemove','keydown','mousedown','scroll','touchstart'].forEach(function(ev){
        document.addEventListener(ev, function(){ sonEtkilesim = Date.now(); }, {passive:true});
    });
    function presencePing(){
        var aktif = (Date.now() - sonEtkilesim) < 15*60*1000;
        try {
            fetch(PING, {
                method:'POST',
                headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded'},
                body:'aktif='+(aktif?1:0),
                keepalive:true
            }).catch(function(){});
        } catch(e){}
    }
    presencePing();
    setInterval(presencePing, 60000);
})();
</script>
@endif

{{-- ═══ AI ASİSTAN (Faz 1 — şimdilik SADECE GÖRÜNÜM / "yakında" modu, backend yok) ═══ --}}
<style>
    .aiw-fab{position:fixed;left:22px;bottom:22px;z-index:8500;width:58px;height:58px;border-radius:50%;
        background:linear-gradient(135deg,#c9c73a,#b8b62e);color:#1a1a0e;border:none;cursor:pointer;
        box-shadow:0 10px 30px rgba(184,182,46,.45);display:flex;align-items:center;justify-content:center;font-size:26px;transition:transform .18s ease}
    .aiw-fab:hover{transform:scale(1.08)}
    .aiw-fab .aiw-dot{position:absolute;top:-3px;right:-3px;background:#8b5cf6;color:#fff;font-size:9px;font-weight:800;padding:2px 6px;border-radius:9px;box-shadow:0 0 0 2px var(--surface,#fff)}
    .aiw-panel{position:fixed;left:22px;bottom:92px;z-index:8500;width:370px;max-width:calc(100vw - 44px);height:520px;max-height:calc(100vh - 130px);
        background:var(--surface,#fff);border:1px solid var(--border,#eceae0);border-radius:18px;box-shadow:0 24px 70px rgba(0,0,0,.22);display:none;flex-direction:column;overflow:hidden}
    .aiw-panel.aiw-open{display:flex;animation:aiwIn .22s ease}
    @keyframes aiwIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:none}}
    .aiw-head{background:#16160f;color:#fff;padding:13px 16px;display:flex;align-items:center;gap:9px}
    .aiw-head .t{font-weight:700;font-size:15px}
    .aiw-head .yak{background:#8b5cf6;color:#fff;font-size:10px;font-weight:800;padding:2px 7px;border-radius:8px;letter-spacing:.3px}
    .aiw-head .x{margin-left:auto;background:none;border:none;color:#b9b98f;cursor:pointer;font-size:22px;line-height:1}
    .aiw-body{flex:1;overflow-y:auto;padding:16px;background:var(--bg-soft,#f6f6f1)}
    .aiw-msg{background:var(--surface,#fff);border:1px solid var(--border,#eceae0);color:var(--text,#1f2419);padding:12px 15px;border-radius:14px;border-bottom-left-radius:4px;font-size:13.5px;line-height:1.6;box-shadow:0 1px 3px rgba(0,0,0,.05)}
    .aiw-chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}
    .aiw-chip{background:var(--surface,#fff);border:1px solid var(--border,#e2e2d6);border-radius:999px;padding:6px 11px;font-size:12px;color:var(--text-muted,#94a3b8)}
    .aiw-foot{padding:11px 12px;border-top:1px solid var(--border,#eceae0);background:var(--surface,#fff);display:flex;gap:8px}
    .aiw-foot input{flex:1;padding:10px 14px;font-size:14px;border:1.5px solid var(--border,#ddd);border-radius:20px;background:var(--bg-soft,#f6f6f1);color:var(--text-muted,#94a3b8)}
    .aiw-foot button{background:#d8d6ba;color:#7a7a55;border:none;border-radius:20px;padding:0 16px;font-weight:800;cursor:not-allowed}
    body.theme-dark .aiw-panel{background:#1b1b1b;border-color:#2c2c2c}
    body.theme-dark .aiw-body{background:#141414}
    body.theme-dark .aiw-msg{background:#242424;border-color:#333;color:#e8e8e0}
    body.theme-dark .aiw-chip{background:#242424;border-color:#333}
    body.theme-dark .aiw-foot{background:#1b1b1b;border-color:#2c2c2c}
    body.theme-dark .aiw-foot input{background:#141414;border-color:#333}
</style>

<button class="aiw-fab" onclick="document.querySelector('.aiw-panel').classList.toggle('aiw-open')" title="AI Asistan (yakında)">
    🤖<span class="aiw-dot">AI</span>
</button>

<div class="aiw-panel">
    <div class="aiw-head">
        <span style="font-size:18px">🤖</span>
        <span class="t">İş Ortağım Asistanı</span>
        <span class="yak">YAKINDA</span>
        <button class="x" onclick="document.querySelector('.aiw-panel').classList.remove('aiw-open')">&times;</button>
    </div>
    <div class="aiw-body">
        <div class="aiw-msg">
            Merhaba! 👋 Ben panel asistanınız. <b>Yakında</b> burada paketler, müşteriler ve faturalar hakkında
            soru sorabilecek; indirim/düzenleme gibi işlemleri yaptırabileceksiniz.
            <br><br><b>Her işlem onayınızla</b> yapılacak ve <b>geri alınabilecek.</b>
            <div class="aiw-chips">
                <span class="aiw-chip">kaç müşteri var</span>
                <span class="aiw-chip">referansları listele</span>
                <span class="aiw-chip">Sosyal Medya'da %10 indirim</span>
            </div>
        </div>
    </div>
    <div class="aiw-foot">
        <input type="text" placeholder="Yakında aktif olacak…" disabled>
        <button disabled>➤</button>
    </div>
</div>

</body>
</html>