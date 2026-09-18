<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Giriş — İş Ortağım Admin</title>

@php
    $loginAyar = \Illuminate\Support\Facades\DB::table('ayarlar')->first();
    $loginFavicon = $loginAyar->favicon ?? null;

    // 1) Ayarlardan özel favicon yüklenmişse onu kullan
    $favCustom = null;
    if ($loginFavicon && file_exists(public_path('tema/uploads/logo/' . $loginFavicon))) {
        $favCustom = asset('tema/uploads/logo/' . $loginFavicon)
            . '?v=' . ($loginAyar->updated_at ? strtotime($loginAyar->updated_at) : time());
    }

    // 2) Yoksa: tema/uploads/favicon/favicon_dn.png (DN markası varsayılan)
    if (!$favCustom && file_exists(public_path('tema/uploads/favicon/favicon_dn.png'))) {
        $favCustom = asset('tema/uploads/favicon/favicon_dn.png')
            . '?v=' . filemtime(public_path('tema/uploads/favicon/favicon_dn.png'));
    }

    $siteAdi = $loginAyar->site_baslik ?? 'İş Ortağım';
@endphp

@if($favCustom)
    <link rel="icon" type="image/png" href="{{ $favCustom }}">
    <link rel="shortcut icon" href="{{ $favCustom }}">
    <link rel="apple-touch-icon" href="{{ $favCustom }}">
@else
    {{-- Son çare: Laravel default --}}
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
@endif
<meta name="theme-color" content="#b8b62e">

{{-- Google Fonts: Poppins (body) + Sora (display) --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Sora:wght@600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">

{{-- Lucide ikonlar --}}
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>

<style>
:root {
    /* Brand */
    --brand: #b8b62e;
    --brand-light: #d4d066;
    --brand-dark: #8a8a1f;
    --brand-soft: rgba(184, 182, 46, .12);

    /* Light tema */
    --bg: #f6f7f3;
    --bg-2: #ffffff;
    --card-bg: #ffffff;
    --text: #1a1d24;
    --text-soft: #4b5563;
    --text-muted: #6b7280;
    --border: #e5e7eb;
    --border-soft: #f3f4f6;
    --input-bg: #ffffff;
    --shadow-sm: 0 1px 2px rgba(0,0,0,.04);
    --shadow-md: 0 4px 12px rgba(0,0,0,.06);
    --shadow-lg: 0 24px 48px -12px rgba(0,0,0,.08);

    /* Semantic */
    --success: #10b981;
    --danger:  #ef4444;
    --warning: #f59e0b;
    --info:    #3b82f6;

    /* Radius */
    --r-sm: 8px;
    --r-md: 12px;
    --r-lg: 16px;
    --r-xl: 24px;
}

body.theme-dark {
    --bg: #0f1115;
    --bg-2: #161922;
    --card-bg: #1a1d27;
    --text: #f3f4f6;
    --text-soft: #d1d5db;
    --text-muted: #9ca3af;
    --border: #2a2f3a;
    --border-soft: #1f242e;
    --input-bg: #0f1115;
    --shadow-sm: 0 1px 2px rgba(0,0,0,.4);
    --shadow-md: 0 4px 12px rgba(0,0,0,.4);
    --shadow-lg: 0 24px 48px -12px rgba(0,0,0,.5);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

html, body {
    height: 100%;
    font-family: 'Poppins', system-ui, sans-serif;
    color: var(--text);
    background: var(--bg);
    -webkit-font-smoothing: antialiased;
    transition: background-color .25s ease, color .25s ease;
}

.font-display {
    font-family: 'Sora', 'Poppins', sans-serif;
    letter-spacing: -0.02em;
}

/* ════════════ LAYOUT ════════════ */
.login-shell {
    min-height: 100vh;
    display: grid;
    grid-template-columns: 1.1fr 1fr;
}
@media (max-width: 900px) {
    .login-shell { grid-template-columns: 1fr; }
    .login-aside { display: none; }
}

/* ════════════ SOL: BRAND PANEL ════════════ */
.login-aside {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 20% 30%, rgba(212,208,102,.18), transparent 55%),
        radial-gradient(circle at 80% 70%, rgba(184,182,46,.14), transparent 55%),
        linear-gradient(135deg, #1a1d27 0%, #0f1115 100%);
    color: #fff;
    padding: 48px;
    display: flex;
    flex-direction: column;
}

.login-aside::before {
    /* Subtle grid */
    content: '';
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(184,182,46,.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(184,182,46,.04) 1px, transparent 1px);
    background-size: 32px 32px;
    pointer-events: none;
}

.brand-logo {
    display: inline-flex;
    align-items: center;
    gap: 14px;
    position: relative;
    z-index: 1;
}
.brand-logo-mark {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: linear-gradient(135deg, var(--brand-light), var(--brand-dark));
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 22px;
    color: #1a1d27;
    box-shadow: 0 12px 32px rgba(184,182,46,.4);
}
.brand-logo-text {
    font-family: 'Sora', sans-serif;
    font-size: 19px;
    font-weight: 700;
    letter-spacing: -0.01em;
}
.brand-logo-text small {
    display: block;
    font-size: 11px;
    font-weight: 500;
    color: var(--brand-light);
    margin-top: 2px;
    text-transform: uppercase;
    letter-spacing: 2px;
}

/* Brand hero */
.brand-hero {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    z-index: 1;
    max-width: 460px;
}

.brand-hero-tagline {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 99px;
    background: rgba(184,182,46,.12);
    border: 1px solid rgba(184,182,46,.25);
    color: var(--brand-light);
    font-size: 12px;
    font-weight: 600;
    width: fit-content;
    margin-bottom: 24px;
}

.brand-hero h2 {
    font-family: 'Sora', sans-serif;
    font-size: 44px;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 18px;
    letter-spacing: -0.03em;
}
.brand-hero h2 .accent {
    background: linear-gradient(135deg, var(--brand-light), var(--brand));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.brand-hero p {
    font-size: 16px;
    line-height: 1.6;
    color: rgba(255,255,255,.7);
    margin-bottom: 32px;
}

.brand-features {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.brand-feature {
    display: flex;
    align-items: center;
    gap: 12px;
    color: rgba(255,255,255,.85);
    font-size: 14px;
}
.brand-feature-icon {
    width: 32px;
    height: 32px;
    border-radius: 9px;
    background: rgba(184,182,46,.15);
    color: var(--brand-light);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.brand-feature-icon i { width: 16px; height: 16px; }

.brand-foot {
    position: relative;
    z-index: 1;
    color: rgba(255,255,255,.4);
    font-size: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.brand-foot a {
    color: rgba(255,255,255,.55);
    text-decoration: none;
}
.brand-foot a:hover { color: var(--brand-light); }

/* ════════════ SAĞ: FORM PANEL ════════════ */
.login-main {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 48px 32px;
    position: relative;
}

.theme-toggle {
    position: absolute;
    top: 24px;
    right: 24px;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    color: var(--text-soft);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all .15s ease;
    box-shadow: var(--shadow-sm);
}
.home-back-link {
    position: absolute;
    top: 24px;
    right: 76px;
    height: 40px;
    padding: 0 14px;
    border-radius: 10px;
    background: var(--card-bg);
    border: 1px solid var(--border);
    color: var(--text-soft);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 13.5px;
    font-weight: 600;
    transition: all .15s ease;
    box-shadow: var(--shadow-sm);
}
.home-back-link:hover {
    border-color: var(--brand);
    color: var(--brand-dark);
}
@media (max-width: 480px) {
    .home-back-link span { display: none; }
    .home-back-link { padding: 0; width: 40px; justify-content: center; }
}
.theme-toggle:hover {
    border-color: var(--brand);
    color: var(--brand-dark);
}
.theme-toggle i { width: 18px; height: 18px; }
.theme-toggle .icon-light { display: none; }
body.theme-dark .theme-toggle .icon-light { display: inline-block; }
body.theme-dark .theme-toggle .icon-dark  { display: none; }

.login-card {
    width: 100%;
    max-width: 440px;
}

.login-card-mobile-brand {
    display: none;
    text-align: center;
    margin-bottom: 28px;
}
@media (max-width: 900px) {
    .login-card-mobile-brand { display: block; }
}

.login-head {
    text-align: left;
    margin-bottom: 32px;
}
.login-head h1 {
    font-family: 'Sora', sans-serif;
    font-size: 32px;
    font-weight: 800;
    color: var(--text);
    letter-spacing: -0.02em;
    margin-bottom: 8px;
}
.login-head p {
    color: var(--text-muted);
    font-size: 14px;
    line-height: 1.6;
}

/* ────── FORM ────── */
.login-form .form-row {
    margin-bottom: 18px;
}

.login-form .form-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 8px;
}
.login-form .form-label i {
    width: 14px;
    height: 14px;
    color: var(--brand-dark);
}

.login-form .input-wrap {
    position: relative;
}

.login-form input[type="text"],
.login-form input[type="email"],
.login-form input[type="password"] {
    width: 100%;
    padding: 13px 16px;
    border-radius: var(--r-md);
    border: 1.5px solid var(--border);
    background: var(--input-bg);
    color: var(--text);
    font-family: inherit;
    font-size: 14px;
    font-weight: 500;
    transition: all .15s ease;
}
.login-form input::placeholder {
    color: var(--text-muted);
    font-weight: 400;
}
.login-form input:focus {
    outline: none;
    border-color: var(--brand);
    box-shadow: 0 0 0 4px var(--brand-soft);
    background: var(--bg-2);
}

.pwd-toggle-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
    padding: 6px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.pwd-toggle-btn:hover {
    background: var(--brand-soft);
    color: var(--brand-dark);
}
.pwd-toggle-btn i { width: 18px; height: 18px; }

.form-extras {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    font-size: 13px;
}
.remember-wrap {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-soft);
    cursor: pointer;
    user-select: none;
}
.remember-wrap input {
    width: 16px;
    height: 16px;
    accent-color: var(--brand);
    cursor: pointer;
}
.forgot-link {
    color: var(--brand-dark);
    text-decoration: none;
    font-weight: 600;
}
.forgot-link:hover {
    color: var(--brand);
    text-decoration: underline;
}

.btn-login {
    width: 100%;
    padding: 14px 18px;
    border: none;
    border-radius: var(--r-md);
    background: linear-gradient(135deg, var(--brand), var(--brand-dark));
    color: #fff;
    font-family: inherit;
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    transition: all .15s ease;
    box-shadow: 0 8px 24px rgba(184,182,46,.3);
    letter-spacing: 0.01em;
}
.btn-login:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 12px 32px rgba(184,182,46,.45);
}
.btn-login:active { transform: translateY(0); }
.btn-login:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}
.btn-login i { width: 18px; height: 18px; }

.login-foot {
    margin-top: 28px;
    padding-top: 24px;
    border-top: 1px dashed var(--border);
    text-align: center;
    color: var(--text-muted);
    font-size: 12px;
}
.login-foot strong { color: var(--brand-dark); font-weight: 600; }

/* ════════════ ALERTLER ════════════ */
.alert-stack {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    max-width: 380px;
    width: calc(100% - 40px);
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
}
.alert {
    pointer-events: auto;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px 14px;
    border-radius: var(--r-md);
    background: var(--card-bg);
    border: 1px solid var(--border);
    box-shadow: var(--shadow-lg);
    animation: slideIn .25s ease-out;
}
.alert-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.alert-icon i { width: 18px; height: 18px; }
.alert-body {
    flex: 1;
    font-size: 13px;
    line-height: 1.5;
    color: var(--text);
    font-weight: 500;
}
.alert-close {
    background: transparent;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 4px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.alert-close:hover { background: var(--border-soft); color: var(--text); }
.alert-close i { width: 14px; height: 14px; }

.alert-danger  { border-color: rgba(239,68,68,.3); }
.alert-danger  .alert-icon { background: rgba(239,68,68,.12); color: var(--danger); }
.alert-success { border-color: rgba(16,185,129,.3); }
.alert-success .alert-icon { background: rgba(16,185,129,.12); color: var(--success); }
.alert-warning { border-color: rgba(245,158,11,.3); }
.alert-warning .alert-icon { background: rgba(245,158,11,.12); color: var(--warning); }
.alert-info    { border-color: rgba(59,130,246,.3); }
.alert-info    .alert-icon { background: rgba(59,130,246,.12); color: var(--info); }

@keyframes slideIn {
    from { transform: translateX(120%); opacity: 0; }
    to   { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to   { transform: translateX(120%); opacity: 0; }
}
.alert.dismissing { animation: slideOut .25s ease-in forwards; }

/* Spinner */
.spinner {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255,255,255,.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    display: none;
}
@keyframes spin { to { transform: rotate(360deg); } }
.btn-login.loading .spinner { display: inline-block; }
.btn-login.loading .btn-text,
.btn-login.loading .btn-icon { display: none; }
</style>
</head>
<body>

{{-- ═══ ALERTLER ═══ --}}
<div class="alert-stack" id="alertStack">
    @if(session('error'))
    <div class="alert alert-danger">
        <div class="alert-icon"><i data-lucide="alert-circle"></i></div>
        <div class="alert-body">{{ session('error') }}</div>
        <button type="button" class="alert-close" onclick="dismissAlert(this)"><i data-lucide="x"></i></button>
    </div>
    @endif

    @if(!session('error') && $errors->any())
    <div class="alert alert-danger">
        <div class="alert-icon"><i data-lucide="alert-triangle"></i></div>
        <div class="alert-body">{{ $errors->first() }}</div>
        <button type="button" class="alert-close" onclick="dismissAlert(this)"><i data-lucide="x"></i></button>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success">
        <div class="alert-icon"><i data-lucide="check-circle-2"></i></div>
        <div class="alert-body">{{ session('success') }}</div>
        <button type="button" class="alert-close" onclick="dismissAlert(this)"><i data-lucide="x"></i></button>
    </div>
    @endif

    @if(session('info'))
    <div class="alert alert-info">
        <div class="alert-icon"><i data-lucide="info"></i></div>
        <div class="alert-body">{{ session('info') }}</div>
        <button type="button" class="alert-close" onclick="dismissAlert(this)"><i data-lucide="x"></i></button>
    </div>
    @endif

    @if(session('warning'))
    <div class="alert alert-warning">
        <div class="alert-icon"><i data-lucide="alert-triangle"></i></div>
        <div class="alert-body">{{ session('warning') }}</div>
        <button type="button" class="alert-close" onclick="dismissAlert(this)"><i data-lucide="x"></i></button>
    </div>
    @endif
</div>

<div class="login-shell">

    {{-- ═══ SOL: BRAND ═══ --}}
    <aside class="login-aside">
        <a href="{{ url('/') }}" class="brand-logo" style="text-decoration:none; color:inherit;" title="Ana Sayfaya Dön">
            <div class="brand-logo-mark">io</div>
            <div class="brand-logo-text">
                {{ $siteAdi }}
                <small>Admin Panel</small>
            </div>
        </a>

        <div class="brand-hero">
            <div class="brand-hero-tagline">
                <i data-lucide="sparkles" style="width:12px;height:12px"></i>
                <span>Yönetim Paneli v2.0</span>
            </div>

            <h2>
                Tüm işinizi <span class="accent">tek panelden</span> yönetin.
            </h2>

            <p>
                Müşterilerden faturalara, bayilerden sayfalara — her şey elinizin altında.
                Hızlı, güvenli ve modern.
            </p>

            <div class="brand-features">
                <div class="brand-feature">
                    <div class="brand-feature-icon"><i data-lucide="shield-check"></i></div>
                    <span>Rol bazlı yetkilendirme</span>
                </div>
                <div class="brand-feature">
                    <div class="brand-feature-icon"><i data-lucide="zap"></i></div>
                    <span>Anlık raporlama ve istatistik</span>
                </div>
                <div class="brand-feature">
                    <div class="brand-feature-icon"><i data-lucide="users"></i></div>
                    <span>CRM, muhasebe ve bayi yönetimi</span>
                </div>
            </div>
        </div>

        <div class="brand-foot">
            <span>© {{ date('Y') }} {{ $siteAdi }}</span>
            <a href="https://ornek.com" target="_blank" rel="noopener">
                DN Kreatif tarafından geliştirildi
            </a>
        </div>
    </aside>

    {{-- ═══ SAĞ: FORM ═══ --}}
    <main class="login-main">

        <a href="{{ url('/') }}" class="home-back-link" title="Ana Sayfaya Dön">
            <i data-lucide="arrow-left" style="width:15px;height:15px"></i>
            <span>Ana Sayfa</span>
        </a>

        <button type="button" class="theme-toggle" id="themeToggle" title="Tema değiştir">
            <i data-lucide="moon" class="icon-dark"></i>
            <i data-lucide="sun" class="icon-light"></i>
        </button>

        <div class="login-card">

            {{-- Mobil brand --}}
            <div class="login-card-mobile-brand">
                <div class="brand-logo" style="justify-content:center">
                    <div class="brand-logo-mark" style="background:linear-gradient(135deg, var(--brand-light), var(--brand-dark));color:#fff">io</div>
                    <div class="brand-logo-text" style="color:var(--text)">
                        {{ $siteAdi }}
                        <small style="color:var(--brand-dark)">Admin Panel</small>
                    </div>
                </div>
            </div>

            <div class="login-head">
                <h1>Hoş Geldin 👋</h1>
                <p>Hesabına giriş yapmak için bilgilerini gir.</p>
            </div>

            <form method="POST" action="{{ route('admin.giris.post') }}" class="login-form" id="loginForm">
                @csrf

                <div class="form-row">
                    <label class="form-label" for="kullanici_adi">
                        <i data-lucide="user"></i>
                        <span>Kullanıcı Adı veya E-posta</span>
                    </label>
                    <div class="input-wrap">
                        <input type="text"
                               id="kullanici_adi"
                               name="kullanici_adi"
                               value="{{ old('kullanici_adi') }}"
                               placeholder="ornek@email.com"
                               autocomplete="username"
                               required
                               autofocus>
                    </div>
                </div>

                <div class="form-row">
                    <label class="form-label" for="sifre">
                        <i data-lucide="lock"></i>
                        <span>Şifre</span>
                    </label>
                    <div class="input-wrap">
                        <input type="password"
                               id="sifre"
                               name="sifre"
                               placeholder="••••••••"
                               autocomplete="current-password"
                               required
                               style="padding-right:44px">
                        <button type="button" class="pwd-toggle-btn" onclick="pwdToggle()" id="pwdToggleBtn" tabindex="-1" aria-label="Şifre göster/gizle">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-extras">
                    <label class="remember-wrap">
                        <input type="checkbox" name="beni_hatirla" value="1">
                        <span>Beni hatırla</span>
                    </label>
                    @if(\Illuminate\Support\Facades\Route::has('admin.sifre.sifirlama'))
                        <a href="{{ route('admin.sifre.sifirlama') }}" class="forgot-link">Şifremi unuttum</a>
                    @endif
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-icon"><i data-lucide="log-in"></i></span>
                    <span class="btn-text">Giriş Yap</span>
                    <span class="spinner"></span>
                </button>
            </form>

            <div class="login-foot">
                Sorun mu yaşıyorsun?
                <strong>gelistirici@ornek.com</strong> ile iletişime geç.
            </div>
        </div>
    </main>
</div>

<script>
// ════════ TEMA ════════
(function initTheme(){
    var saved = localStorage.getItem('admin_theme');
    if (saved === 'dark') {
        document.body.classList.add('theme-dark');
    }
})();

document.getElementById('themeToggle').addEventListener('click', function(){
    document.body.classList.toggle('theme-dark');
    localStorage.setItem('admin_theme',
        document.body.classList.contains('theme-dark') ? 'dark' : 'light');
});

// ════════ ŞİFRE TOGGLE ════════
function pwdToggle(){
    var inp = document.getElementById('sifre');
    var btn = document.getElementById('pwdToggleBtn');
    if (inp.type === 'password') {
        inp.type = 'text';
        btn.innerHTML = '<i data-lucide="eye-off"></i>';
    } else {
        inp.type = 'password';
        btn.innerHTML = '<i data-lucide="eye"></i>';
    }
    if (window.lucide) window.lucide.createIcons();
}

// ════════ FORM SUBMIT INDICATOR ════════
document.getElementById('loginForm').addEventListener('submit', function(){
    var btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.classList.add('loading');
});

// ════════ ALERT DISMISS ════════
function dismissAlert(btn){
    var alert = btn.closest('.alert');
    if (!alert) return;
    alert.classList.add('dismissing');
    setTimeout(function(){ alert.remove(); }, 250);
}

// Otomatik kapatma (sadece success/info)
setTimeout(function(){
    document.querySelectorAll('.alert-success, .alert-info').forEach(function(a){
        a.classList.add('dismissing');
        setTimeout(function(){ a.remove(); }, 250);
    });
}, 5000);

// ════════ LUCIDE INIT ════════
if (window.lucide) {
    window.lucide.createIcons();
}
</script>

</body>
</html>