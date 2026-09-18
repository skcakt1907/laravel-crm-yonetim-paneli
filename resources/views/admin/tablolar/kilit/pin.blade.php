<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>🔒 Tablolar PIN — İş Ortağım</title>
<link rel="shortcut icon" href="{{ asset('tema/uploads/favicon/favicon_dn.png') }}">
<link rel="icon" type="image/png" href="{{ asset('tema/uploads/favicon/favicon_dn.png') }}">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
    --brand: #b8b62e;
    --brand-dark: #8a8a1f;
    --brand-soft: rgba(184, 182, 46, 0.08);
    --brand-medium: rgba(184, 182, 46, 0.18);

    --bg: #f8fafc;
    --surface: #ffffff;
    --bg-subtle: #f1f5f9;
    --border: rgba(15, 23, 42, 0.08);
    --text: #0f172a;
    --text-secondary: #475569;
    --text-muted: #94a3b8;

    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;

    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
    --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 12px 28px rgba(0, 0, 0, 0.10);
    --shadow-xl: 0 24px 60px rgba(0, 0, 0, 0.14);

    --radius-md: 10px;
    --radius-lg: 14px;
    --radius-xl: 20px;
}

body.theme-dark {
    --bg: #0a0a0a;
    --surface: #141414;
    --bg-subtle: #1a1a1a;
    --border: rgba(255, 255, 255, 0.08);
    --text: #f1f5f9;
    --text-secondary: rgba(241, 245, 249, 0.7);
    --text-muted: rgba(241, 245, 249, 0.45);
}

* { margin: 0; padding: 0; box-sizing: border-box; -webkit-font-smoothing: antialiased; }

body {
    font-family: 'Poppins', system-ui, sans-serif;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    overflow-x: hidden;
    position: relative;
}

/* Animated blobs */
@keyframes blob {
    0%, 100% { transform: translate(0, 0) scale(1); }
    33% { transform: translate(30px, -50px) scale(1.1); }
    66% { transform: translate(-20px, 30px) scale(0.9); }
}
.blob {
    position: fixed;
    border-radius: 50%;
    filter: blur(120px);
    pointer-events: none;
    animation: blob 15s ease-in-out infinite;
    opacity: 0.5;
}
.blob-1 { background: var(--brand); width: 400px; height: 400px; top: -100px; left: -100px; }
.blob-2 { background: #f59e0b; width: 400px; height: 400px; bottom: -100px; right: -100px; animation-delay: 5s; }

body.theme-dark .blob { opacity: 0.2; }

/* PIN Card */
.pin-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    padding: 44px 36px;
    max-width: 440px;
    width: 100%;
    box-shadow: var(--shadow-xl);
    position: relative;
    z-index: 10;
}

.pin-icon {
    width: 82px;
    height: 82px;
    margin: 0 auto 22px;
    background: linear-gradient(135deg, var(--brand), var(--brand-dark));
    border-radius: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 38px;
    box-shadow: 0 12px 32px rgba(184, 182, 46, 0.35);
    position: relative;
}

h1 {
    font-size: 26px;
    font-weight: 700;
    text-align: center;
    margin-bottom: 6px;
    color: var(--text);
    letter-spacing: -0.02em;
}

.subtitle {
    text-align: center;
    color: var(--text-secondary);
    font-size: 14px;
    margin-bottom: 30px;
    line-height: 1.6;
}

/* User info */
.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    background: var(--bg-subtle);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    margin-bottom: 20px;
}
.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--brand), var(--brand-dark));
    color: #000;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 15px;
    flex-shrink: 0;
}
.user-name {
    color: var(--text);
    font-weight: 600;
    font-size: 14px;
}
.user-sub {
    color: var(--text-muted);
    font-size: 12px;
}

/* PIN form */
.pin-form {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.pin-label {
    display: block;
    color: var(--brand-dark);
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
body.theme-dark .pin-label { color: var(--brand); }

.pin-input {
    width: 100%;
    background: var(--bg);
    border: 2px solid var(--border);
    color: var(--text);
    padding: 16px 22px;
    border-radius: var(--radius-md);
    font-family: 'Poppins', sans-serif;
    font-size: 30px;
    font-weight: 700;
    text-align: center;
    letter-spacing: 12px;
    transition: all 0.2s;
    outline: none;
}
.pin-input:focus {
    border-color: var(--brand);
    background: var(--surface);
    box-shadow: 0 0 0 3px var(--brand-medium);
}
.pin-input::placeholder {
    color: var(--text-muted);
    letter-spacing: 8px;
    font-size: 22px;
    opacity: 0.4;
}

.btn-submit {
    background: linear-gradient(135deg, var(--brand), var(--brand-dark));
    color: #000;
    font-weight: 700;
    padding: 14px 28px;
    border-radius: var(--radius-md);
    border: none;
    cursor: pointer;
    font-size: 15px;
    font-family: inherit;
    box-shadow: 0 8px 20px rgba(184, 182, 46, 0.35);
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}
.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(184, 182, 46, 0.5);
}
.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.geri-link {
    text-align: center;
    margin-top: 22px;
}
.geri-link a {
    color: var(--text-muted);
    text-decoration: none;
    font-size: 13px;
    transition: color 0.2s;
}
.geri-link a:hover { color: var(--brand-dark); }

.info-box {
    background: var(--brand-soft);
    border: 1px solid var(--brand-medium);
    border-radius: var(--radius-md);
    padding: 12px 16px;
    margin-top: 20px;
    font-size: 12px;
    color: var(--text-secondary);
    line-height: 1.6;
}
.info-box strong { color: var(--brand-dark); }
body.theme-dark .info-box strong { color: var(--brand); }

/* Alerts */
.alert {
    padding: 12px 16px;
    border-radius: var(--radius-md);
    margin-bottom: 18px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.alert-error {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--danger);
    animation: shake 0.4s;
}
.alert-success {
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: var(--success);
}
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
}

/* Theme toggle (top-right floating) */
.theme-toggle {
    position: fixed;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--surface);
    border: 1px solid var(--border);
    color: var(--text);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: var(--shadow-md);
    z-index: 100;
    transition: all 0.2s;
}
.theme-toggle:hover {
    background: var(--brand-soft);
    transform: scale(1.05);
}
</style>
</head>
<body>

<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<button class="theme-toggle" id="themeToggle" title="Tema değiştir">🌗</button>

<div class="pin-card">
    <div class="pin-icon">🔒</div>

    <h1>Tablolar Kilidi</h1>
    <p class="subtitle">Bu modüle erişim için PIN gerekli</p>

    @if(session('error'))
        <div class="alert alert-error">
            <span style="font-size:18px">❌</span>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success">
            <span style="font-size:18px">✅</span>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Kullanıcı bilgisi --}}
    @php
        $adi = session('admin_adi', 'Admin');
        $initial = mb_substr($adi, 0, 1, 'UTF-8');
    @endphp
    <div class="user-info">
        <div class="user-avatar">{{ strtoupper($initial) }}</div>
        <div>
            <div class="user-name">{{ $adi }}</div>
            <div class="user-sub">Yetkilisin, PIN doğrula</div>
        </div>
    </div>

    <form action="{{ route('admin.tablolar.kilit.pin.dogrula') }}" method="POST" class="pin-form" id="pinForm">
        @csrf
        <div>
            <label class="pin-label" for="pin-input">🔑 PIN Kodu</label>
            <input
                type="password"
                name="pin"
                id="pin-input"
                class="pin-input"
                placeholder="••••••"
                inputmode="numeric"
                autocomplete="off"
                required
                autofocus
                maxlength="20">
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">
            <span>🔓 Kilidi Aç</span>
        </button>
    </form>

    <div class="info-box">
        💡 <strong>Bilgi:</strong> PIN doğru girilirse 4 saat boyunca tekrar sorulmaz. PIN'i unutursan Patron'a sor.
    </div>

    <div class="geri-link">
        <a href="{{ route('admin.dashboard') }}">← Anasayfaya dön</a>
    </div>
</div>

<script>
// Tema uyumu (admin paneliyle aynı key)
const savedTheme = localStorage.getItem('admin_theme');
if (savedTheme === 'dark') document.body.classList.add('theme-dark');

document.getElementById('themeToggle').addEventListener('click', function() {
    document.body.classList.toggle('theme-dark');
    localStorage.setItem('admin_theme', document.body.classList.contains('theme-dark') ? 'dark' : 'light');
});

// Form submit indicator
document.getElementById('pinForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳ Doğrulanıyor...</span>';
});

// PIN input formatı - sadece alfanumerik
document.getElementById('pin-input').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/[^a-zA-Z0-9]/g, '');
});
</script>

</body>
</html>