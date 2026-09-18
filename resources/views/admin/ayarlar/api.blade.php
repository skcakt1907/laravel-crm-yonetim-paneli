@extends('admin._layout')

@section('title', 'API Entegrasyonları')

@push('head')
@include('admin.ayarlar._partials.styles')
<style>
    .integration-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        margin-bottom: 14px;
        overflow: hidden;
    }
    .integration-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        background: var(--bg-subtle);
        border-bottom: 1px solid var(--border);
    }
    .integration-logo {
        width: 40px; height: 40px;
        border-radius: var(--radius-md);
        background: var(--surface);
        border: 1px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-weight: 700;
        font-size: 12px;
    }
    .integration-name { font-weight: 600; font-size: 14.5px; }
    .integration-desc { font-size: 11.5px; color: var(--text-muted); }

    .api-input {
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
        background: none;
        border: none;
        cursor: pointer;
        color: var(--text-muted);
        padding: 4px;
    }
    .pw-toggle input { padding-right: 38px !important; }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">API Entegrasyon</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="key"></i>
            API Entegrasyonları
        </h1>
        <div class="page-subtitle">Ödeme servisleri ve diğer API anahtarları</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'api'])

    <div class="ayarlar-content">
        <form action="{{ route('admin.ayarlar.api.post') }}" method="POST">
            @csrf

            <div class="info-card">
                <div class="ic"><i data-lucide="shield"></i></div>
                <div class="body">
                    API anahtarlarınız güvenli şekilde DB'de saklanır. Aşağıdaki bilgileri kimseyle paylaşmayın.
                </div>
            </div>

            {{-- PAYTR --}}
            <div class="integration-card">
                <div class="integration-head">
                    <div class="integration-logo" style="background:#FF6B35;color:#fff">PT</div>
                    <div style="flex:1">
                        <div class="integration-name">PayTR</div>
                        <div class="integration-desc">Türkiye'nin önde gelen ödeme altyapısı</div>
                    </div>
                </div>
                <div style="padding:16px">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label class="form-label">Merchant ID</label>
                            <input type="text" name="paytr_merchant_id"
                                   value="{{ old('paytr_merchant_id', $ayarlar->paytr_merchant_id ?? '') }}"
                                   class="form-input api-input"
                                   placeholder="123456">
                        </div>
                        <div class="form-group full pw-toggle">
                            <label class="form-label">Merchant Key</label>
                            <input type="password" id="paytr_key" name="paytr_merchant_key"
                                   value="{{ old('paytr_merchant_key', $ayarlar->paytr_merchant_key ?? '') }}"
                                   class="form-input api-input">
                            <button type="button" class="toggle-btn" onclick="togglePw('paytr_key', this)">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                        <div class="form-group full pw-toggle">
                            <label class="form-label">Merchant Salt</label>
                            <input type="password" id="paytr_salt" name="paytr_merchant_salt"
                                   value="{{ old('paytr_merchant_salt', $ayarlar->paytr_merchant_salt ?? '') }}"
                                   class="form-input api-input">
                            <button type="button" class="toggle-btn" onclick="togglePw('paytr_salt', this)">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- IYZICO --}}
            <div class="integration-card">
                <div class="integration-head">
                    <div class="integration-logo" style="background:#1E64FF;color:#fff">IY</div>
                    <div style="flex:1">
                        <div class="integration-name">iyzico</div>
                        <div class="integration-desc">Uluslararası ödeme platformu</div>
                    </div>
                </div>
                <div style="padding:16px">
                    <div class="form-grid">
                        <div class="form-group full pw-toggle">
                            <label class="form-label">API Key</label>
                            <input type="password" id="iyzico_api" name="iyzico_api_key"
                                   value="{{ old('iyzico_api_key', $ayarlar->iyzico_api_key ?? '') }}"
                                   class="form-input api-input"
                                   placeholder="sandbox-...">
                            <button type="button" class="toggle-btn" onclick="togglePw('iyzico_api', this)">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                        <div class="form-group full pw-toggle">
                            <label class="form-label">Secret Key</label>
                            <input type="password" id="iyzico_secret" name="iyzico_secret_key"
                                   value="{{ old('iyzico_secret_key', $ayarlar->iyzico_secret_key ?? '') }}"
                                   class="form-input api-input">
                            <button type="button" class="toggle-btn" onclick="togglePw('iyzico_secret', this)">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Base URL</label>
                            <input type="url" name="iyzico_base_url"
                                   value="{{ old('iyzico_base_url', $ayarlar->iyzico_base_url ?? 'https://sandbox-api.iyzipay.com') }}"
                                   class="form-input api-input"
                                   placeholder="https://sandbox-api.iyzipay.com">
                            <small class="form-help">
                                Test: <code>https://sandbox-api.iyzipay.com</code> &middot;
                                Canlı: <code>https://api.iyzipay.com</code>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="lock" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Anahtarlar şifreli olarak DB'de saklanır
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>API Ayarlarını Kaydet</span>
                </button>
            </div>
        </form>
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