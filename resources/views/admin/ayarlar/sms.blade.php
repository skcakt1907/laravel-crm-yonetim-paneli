@extends('admin._layout')

@section('title', 'SMS Ayarları')

@push('head')
@include('admin.ayarlar._partials.styles')
<style>
    .sms-input {
        font-family: monospace !important;
        font-size: 12.5px !important;
    }

    .pw-toggle { position: relative; }
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
        background: linear-gradient(135deg, rgba(59,130,246,0.08), transparent);
        border: 1px solid rgba(59,130,246,0.2);
        border-left: 4px solid #3b82f6;
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
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">SMS Ayarları</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="message-square"></i>
            SMS Ayarları
        </h1>
        <div class="page-subtitle">SMS sağlayıcı entegrasyonu ve test gönderimi</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'sms'])

    <div class="ayarlar-content">
        <div class="info-card">
            <div class="ic"><i data-lucide="info"></i></div>
            <div class="body">
                <strong>Netgsm, İletimerkezi, Mutlucell</strong> gibi Türkiye SMS sağlayıcılarıyla uyumludur.
                Sağlayıcınızdan aldığınız API bilgilerini girin.
            </div>
        </div>

        <form action="{{ route('admin.ayarlar.sms.post') }}" method="POST">
            @csrf

            <div class="section">
                <div class="section-title">
                    <i data-lucide="key-round"></i>
                    <span>SMS Sağlayıcı Bilgileri</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">SMS Başlığı (Mesaj Başlığı)</label>
                        <input type="text" name="sms_baslik"
                               value="{{ old('sms_baslik', $ayarlar->sms_baslik ?? '') }}"
                               class="form-input"
                               maxlength="11"
                               placeholder="DNKREATIF">
                        <small class="form-help">Max 11 karakter, sağlayıcıdan onaylı olmalı</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="sms_kullanici_adi"
                               value="{{ old('sms_kullanici_adi', $ayarlar->sms_kullanici_adi ?? '') }}"
                               class="form-input sms-input">
                    </div>

                    <div class="form-group pw-toggle">
                        <label class="form-label">Şifre</label>
                        <input type="password" id="smsPw" name="sms_sifre"
                               value="{{ old('sms_sifre', $ayarlar->sms_sifre ?? '') }}"
                               class="form-input sms-input"
                               autocomplete="new-password">
                        <button type="button" class="toggle-btn" onclick="togglePw('smsPw', this)">
                            <i data-lucide="eye" style="width:16px;height:16px"></i>
                        </button>
                    </div>

                    <div class="form-group pw-toggle">
                        <label class="form-label">API Anahtarı</label>
                        <input type="password" id="smsApi" name="sms_api_key"
                               value="{{ old('sms_api_key', $ayarlar->sms_api_key ?? '') }}"
                               class="form-input sms-input">
                        <button type="button" class="toggle-btn" onclick="togglePw('smsApi', this)">
                            <i data-lucide="eye" style="width:16px;height:16px"></i>
                        </button>
                        <small class="form-help">Bazı sağlayıcılar isteğe bağlı kullanır</small>
                    </div>
                </div>
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="lock" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Bilgiler şifreli saklanır
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>SMS Ayarlarını Kaydet</span>
                </button>
            </div>
        </form>

        {{-- TEST SMS --}}
        <div class="test-card">
            <div style="display:flex;align-items:center;gap:8px;font-weight:600;margin-bottom:6px">
                <i data-lucide="zap" style="width:16px;height:16px;color:#3b82f6"></i>
                <span>Test SMS Gönder</span>
            </div>
            <div style="font-size:12.5px;color:var(--text-secondary)">
                SMS ayarlarınızın doğru çalıştığını test edin
            </div>
            <form action="{{ route('admin.ayarlar.sms.test') }}" method="POST" class="test-form">
                @csrf
                <input type="tel" name="test_telefon"
                       class="form-input"
                       placeholder="05XX XXX XX XX"
                       required>
                <button type="submit" class="btn btn-info">
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
</script>

@endsection