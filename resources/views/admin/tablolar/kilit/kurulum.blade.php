<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>🔧 Tablolar Kurulumu — İş Ortağım</title>
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
    --info: #3b82f6;
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
}

.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-xl);
    padding: 40px;
    max-width: 620px;
    width: 100%;
    box-shadow: var(--shadow-xl);
}

.icon-big {
    font-size: 48px;
    text-align: center;
    margin-bottom: 14px;
}

h1 {
    font-size: 24px;
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
    margin-bottom: 28px;
    line-height: 1.6;
}

.field {
    margin-bottom: 20px;
}

.field-label {
    display: block;
    color: var(--brand-dark);
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
}
body.theme-dark .field-label { color: var(--brand); }

input[type="password"], input[type="text"] {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--border);
    color: var(--text);
    padding: 12px 16px;
    border-radius: var(--radius-md);
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    transition: all 0.2s;
    outline: none;
}
input[type="password"]:focus, input[type="text"]:focus {
    border-color: var(--brand);
    box-shadow: 0 0 0 3px var(--brand-medium);
}

.admin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 6px;
    padding: 12px;
    background: var(--bg-subtle);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    max-height: 280px;
    overflow-y: auto;
}
.admin-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all 0.15s;
    font-size: 13px;
}
.admin-item:hover {
    background: var(--brand-soft);
    border-color: var(--brand-medium);
}
.admin-item.selected {
    background: var(--brand-soft);
    border-color: var(--brand);
}
.admin-item input[type="checkbox"] {
    width: auto;
    margin: 0;
    flex-shrink: 0;
}
.admin-item .info {
    flex: 1;
    min-width: 0;
}
.admin-item .info-name {
    font-weight: 600;
    font-size: 13px;
    color: var(--text);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.admin-item .info-sub {
    color: var(--text-muted);
    font-size: 11px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.btn-y {
    background: linear-gradient(135deg, var(--brand), var(--brand-dark));
    color: #000;
    font-weight: 700;
    padding: 14px 28px;
    border-radius: var(--radius-md);
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-family: inherit;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
    box-shadow: 0 8px 20px rgba(184, 182, 46, 0.3);
}
.btn-y:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(184, 182, 46, 0.45);
}

.alert {
    padding: 12px 16px;
    border-radius: var(--radius-md);
    margin-bottom: 18px;
    font-size: 13px;
}
.alert-error {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--danger);
}
.alert-info {
    background: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: var(--info);
}

.help-text {
    color: var(--text-muted);
    font-size: 12px;
    margin-top: 6px;
    line-height: 1.5;
}

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
    z-index: 100;
}
</style>
</head>
<body>

<button class="theme-toggle" id="themeToggle" title="Tema">🌗</button>

<div class="card">
    <div class="icon-big">🔧</div>
    <h1>Tablolar Modülü Kurulumu</h1>
    <p class="subtitle">PIN belirle ve hangi adminlerin erişebileceğini seç.<br>Senin (Patron) erişimin otomatik eklenir.</p>

    @if($errors->any())
        <div class="alert alert-error">⚠️ {{ $errors->first() }}</div>
    @endif

    @if(session('info'))
        <div class="alert alert-info">ℹ️ {{ session('info') }}</div>
    @endif

    <form action="{{ route('admin.tablolar.kilit.kurulum.kaydet') }}" method="POST">
        @csrf

        {{-- PIN --}}
        <div class="field">
            <label class="field-label">🔑 PIN Belirle (en az 4 karakter)</label>
            <input type="password" name="pin" placeholder="Yeni PIN..." required minlength="4" maxlength="20" autocomplete="new-password">
            <div class="help-text">💡 Rakam veya harf kullanabilirsin. Önerilen: 6 haneli sayı.</div>
        </div>

        <div class="field">
            <label class="field-label">🔑 PIN Tekrar</label>
            <input type="password" name="pin_tekrar" placeholder="Aynısını tekrar yaz..." required minlength="4" maxlength="20" autocomplete="new-password">
        </div>

        {{-- Yetkili Adminler --}}
        <div class="field">
            <label class="field-label">👥 Yetkili Adminler</label>
            <div class="admin-grid" id="adminGrid">
                @foreach($adminler as $a)
                    <label class="admin-item" data-admin-item>
                        <input type="checkbox" name="yetkili_ids[]" value="{{ $a->id }}">
                        <div class="info">
                            <div class="info-name">{{ $a->adi }}</div>
                            <div class="info-sub">{{ '@'.$a->kullaniciadi }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
            <div class="help-text">💡 Sen (Patron) otomatik olarak eklenirsin, ayrıca seçmen gerekmez.</div>
        </div>

        <button type="submit" class="btn-y">
            ✅ Kurulumu Tamamla
        </button>
    </form>
</div>

<script>
const savedTheme = localStorage.getItem('admin_theme');
if (savedTheme === 'dark') document.body.classList.add('theme-dark');

document.getElementById('themeToggle').addEventListener('click', function() {
    document.body.classList.toggle('theme-dark');
    localStorage.setItem('admin_theme', document.body.classList.contains('theme-dark') ? 'dark' : 'light');
});

document.querySelectorAll('[data-admin-item]').forEach(function(item) {
    const checkbox = item.querySelector('input[type="checkbox"]');
    checkbox.addEventListener('change', function() {
        if (this.checked) item.classList.add('selected');
        else item.classList.remove('selected');
    });
});
</script>

</body>
</html>