@extends('admin._layout')

@section('title', 'Mail Ayarları')

@push('head')
@include('admin.ayarlar._partials.styles')
<style>
    .smtp-input {
        font-family: monospace !important;
        font-size: 12.5px !important;
    }

    .pw-toggle {
        position: relative;
    }
    .pw-toggle .toggle-btn {
        position: absolute;
        right: 10px; top: 50%;
        transform: translateY(-50%);
        background: none; border: none; cursor: pointer;
        color: var(--text-muted);
        padding: 4px;
    }
    .pw-toggle input { padding-right: 38px !important; }

    .test-card {
        background: linear-gradient(135deg, rgba(16,185,129,0.08), transparent);
        border: 1px solid rgba(16,185,129,0.2);
        border-left: 4px solid #10b981;
        border-radius: var(--radius-md);
        padding: 16px;
        margin-top: 14px;
    }
    .test-form {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 8px;
        margin-top: 10px;
    }
    @media (max-width: 600px) {
        .test-form { grid-template-columns: 1fr; }
    }

    .preset-pills {
        display: flex; gap: 6px; flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .preset-pill {
        padding: 6px 12px;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 99px;
        font-size: 12px;
        cursor: pointer;
        font-weight: 500;
    }
    .preset-pill:hover {
        background: var(--brand-soft);
        border-color: var(--brand);
        color: var(--brand-dark);
    }
    body.theme-dark .preset-pill:hover { color: var(--brand); }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">Mail / SMTP</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="mail"></i>
            SMTP Mail Ayarları
        </h1>
        <div class="page-subtitle">E-posta gönderim sunucusu ayarları ve test</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'mail'])

    <div class="ayarlar-content">
        <div class="info-card">
            <div class="ic"><i data-lucide="info"></i></div>
            <div class="body">
                <strong>Not:</strong> .env dosyasındaki <code>MAIL_*</code> değişkenleri bu ayarları ezer. Boş bırakırsanız .env değerleri kullanılır.
            </div>
        </div>

        {{-- SMTP AYARLARI --}}
        <form action="{{ route('admin.ayarlar.mail.post') }}" method="POST">
            @csrf

            <div class="section">
                <div class="section-title">
                    <i data-lucide="server"></i>
                    <span>SMTP Sunucu Ayarları</span>
                </div>

                {{-- Hızlı şablonlar --}}
                <div class="preset-pills">
                    <span class="preset-pill" onclick="setPreset('gmail')">📧 Gmail</span>
                    <span class="preset-pill" onclick="setPreset('outlook')">📨 Outlook</span>
                    <span class="preset-pill" onclick="setPreset('yandex')">📩 Yandex</span>
                    <span class="preset-pill" onclick="setPreset('cpanel')">🌐 cPanel</span>
                </div>

                <div class="form-grid">
                    <div class="form-group" style="grid-column: span 2">
                        <label class="form-label">SMTP Sunucu (Host)</label>
                        <input type="text" name="mail_host"
                               value="{{ old('mail_host', $ayarlar->mail_host ?? '') }}"
                               class="form-input smtp-input"
                               placeholder="smtp.gmail.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Port</label>
                        <input type="number" name="mail_port"
                               value="{{ old('mail_port', $ayarlar->mail_port ?? 587) }}"
                               class="form-input smtp-input"
                               placeholder="587">
                        <small class="form-help">587 (TLS) / 465 (SSL) / 25</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Şifreleme</label>
                        <select name="mail_encryption" class="form-select">
                            <option value="tls" {{ ($ayarlar->mail_encryption ?? 'tls') === 'tls' ? 'selected' : '' }}>TLS</option>
                            <option value="ssl" {{ ($ayarlar->mail_encryption ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                            <option value="" {{ ($ayarlar->mail_encryption ?? '') === '' ? 'selected' : '' }}>Yok</option>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label class="form-label">Kullanıcı Adı / E-posta</label>
                        <input type="text" name="mail_username"
                               value="{{ old('mail_username', $ayarlar->mail_username ?? '') }}"
                               class="form-input smtp-input"
                               placeholder="info@firma.com">
                    </div>

                    <div class="form-group full pw-toggle">
                        <label class="form-label">Şifre</label>
                        <input type="password" id="mailPw" name="mail_password"
                               value="{{ old('mail_password', $ayarlar->mail_password ?? '') }}"
                               class="form-input smtp-input"
                               autocomplete="new-password">
                        <button type="button" class="toggle-btn" onclick="togglePw('mailPw', this)">
                            <i data-lucide="eye" style="width:16px;height:16px"></i>
                        </button>
                        <small class="form-help">Gmail için "uygulama şifresi" kullanın</small>
                    </div>
                </div>
            </div>

            <div class="section" style="margin-top:16px">
                <div class="section-title">
                    <i data-lucide="at-sign"></i>
                    <span>Gönderen Bilgileri</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Gönderen E-posta</label>
                        <input type="email" name="mail_from_address"
                               value="{{ old('mail_from_address', $ayarlar->mail_from_address ?? '') }}"
                               class="form-input smtp-input"
                               placeholder="noreply@firma.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Gönderen Adı</label>
                        <input type="text" name="mail_from_name"
                               value="{{ old('mail_from_name', $ayarlar->mail_from_name ?? '') }}"
                               class="form-input"
                               placeholder="DN Kreatif">
                    </div>
                </div>
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="lock" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Şifre değerleri DB'de saklanır
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>SMTP Ayarlarını Kaydet</span>
                </button>
            </div>
        </form>

        {{-- TEST MAIL - AYRI FORM --}}
        <div class="test-card">
            <div style="display:flex;align-items:center;gap:8px;font-weight:600;margin-bottom:6px">
                <i data-lucide="zap" style="width:16px;height:16px;color:#10b981"></i>
                <span>Test E-postası Gönder</span>
            </div>
            <div style="font-size:12.5px;color:var(--text-secondary)">
                Yukarıdaki SMTP ayarlarının doğru çalıştığını test edin
            </div>
            <form action="{{ route('admin.ayarlar.mail.post') }}" method="POST" class="test-form">
                @csrf
                <input type="hidden" name="test_mail" value="1">
                <input type="email" name="test_email"
                       class="form-input"
                       placeholder="test@gmail.com"
                       required>
                <button type="submit" class="btn btn-success">
                    <i data-lucide="send"></i>
                    <span>Test Gönder</span>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function togglePw(id, btn) {
    const inp = document.getElementById(id);
    if (inp.type === 'password') {
        inp.type = 'text';
        btn.innerHTML = '<i data-lucide="eye-off" style="width:16px;height:16px"></i>';
    } else {
        inp.type = 'password';
        btn.innerHTML = '<i data-lucide="eye" style="width:16px;height:16px"></i>';
    }
    if (window.lucide) lucide.createIcons();
}

const presets = {
    gmail:   { host: 'smtp.gmail.com',    port: 587, enc: 'tls' },
    outlook: { host: 'smtp-mail.outlook.com', port: 587, enc: 'tls' },
    yandex:  { host: 'smtp.yandex.com',   port: 465, enc: 'ssl' },
    cpanel:  { host: 'mail.firma.com',    port: 465, enc: 'ssl' },
};

function setPreset(key) {
    const p = presets[key];
    if (!p) return;
    document.querySelector('[name="mail_host"]').value = p.host;
    document.querySelector('[name="mail_port"]').value = p.port;
    document.querySelector('[name="mail_encryption"]').value = p.enc;
}
</script>

@endsection