@extends('admin._layout')

@section('title', 'Sanal POS Ayarları')

@push('head')
@include('admin.ayarlar._partials.styles')
<style>
    .pos-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        margin-bottom: 14px;
        overflow: hidden;
        transition: all 0.2s;
    }
    .pos-card.aktif {
        border-color: var(--brand);
        box-shadow: 0 0 0 3px var(--brand-soft);
    }

    .pos-head {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px 16px;
        background: var(--bg-subtle);
        border-bottom: 1px solid var(--border);
    }
    .pos-logo {
        width: 44px; height: 44px;
        border-radius: var(--radius-md);
        background: var(--surface);
        border: 1px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-weight: 800;
        font-size: 12px;
    }
    .pos-name { font-weight: 600; font-size: 15px; }
    .pos-desc { font-size: 11.5px; color: var(--text-muted); }

    .pos-body {
        padding: 16px;
        display: none;
    }
    .pos-card.aktif .pos-body { display: block; }

    .api-input {
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

    .checkbox-pills {
        display: flex; gap: 8px; flex-wrap: wrap;
    }
    .checkbox-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 99px;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        cursor: pointer;
        font-size: 12.5px;
        font-weight: 600;
        transition: all 0.15s;
    }
    .checkbox-pill input { margin: 0; }
    .checkbox-pill.checked {
        background: var(--brand);
        color: #000;
        border-color: var(--brand-dark);
    }
</style>
@endpush

@section('content')

<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.ayarlar.index') }}">Ayarlar</a>
    <span class="sep">/</span>
    <span class="current">Sanal POS</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="credit-card"></i>
            Sanal POS Ayarları
        </h1>
        <div class="page-subtitle">Ödeme yöntemleri ve sanal POS entegrasyonları</div>
    </div>
</div>

<div class="ayarlar-layout">
    @include('admin.ayarlar._partials.nav', ['active' => 'sanal'])

    <div class="ayarlar-content">
        <form action="{{ route('admin.ayarlar.sanal.post') }}" method="POST">
            @csrf

            <div class="info-card">
                <div class="ic"><i data-lucide="info"></i></div>
                <div class="body">
                    Her ödeme metodu için "Aktif" toggle'ını açın ve gerekli bilgileri doldurun.
                    Pasif yöntemler ödeme sayfasında görünmez.
                </div>
            </div>

            {{-- GENEL AYARLAR --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="settings-2"></i>
                    <span>Genel Ödeme Ayarları</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Varsayılan Ödeme Yöntemi</label>
                        @php $defaultPay = $ayarlar->defaultpayment ?? 'iyzico'; @endphp
                        <select name="defaultpayment" class="form-select">
                            <option value="iyzico" {{ $defaultPay === 'iyzico' ? 'selected' : '' }}>iyzico</option>
                            <option value="paytr" {{ $defaultPay === 'paytr' ? 'selected' : '' }}>PayTR</option>
                            <option value="havale" {{ $defaultPay === 'havale' ? 'selected' : '' }}>Havale/EFT</option>
                            <option value="bakiye" {{ $defaultPay === 'bakiye' ? 'selected' : '' }}>Bakiye</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Para Birimi</label>
                        @php $pb = $ayarlar->para_birimi ?? 'TRY'; @endphp
                        <select name="para_birimi" class="form-select">
                            <option value="TRY" {{ $pb === 'TRY' ? 'selected' : '' }}>₺ TRY (Türk Lirası)</option>
                            <option value="USD" {{ $pb === 'USD' ? 'selected' : '' }}>$ USD (Dolar)</option>
                            <option value="EUR" {{ $pb === 'EUR' ? 'selected' : '' }}>€ EUR (Euro)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">KDV Oranı (%)</label>
                        <input type="number" step="0.01" min="0" max="100" name="kdv"
                               value="{{ old('kdv', $ayarlar->kdv ?? 20) }}"
                               class="form-input">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Min. Bakiye Yükleme</label>
                        <input type="number" step="0.01" min="0" name="min_bakiye_yukleme"
                               value="{{ old('min_bakiye_yukleme', $ayarlar->min_bakiye_yukleme ?? 50) }}"
                               class="form-input">
                    </div>
                </div>
            </div>

            {{-- IYZICO --}}
            @php $iyzicoAktif = (int)($ayarlar->iyzico_aktif ?? 0) === 1; @endphp
            <div class="pos-card {{ $iyzicoAktif ? 'aktif' : '' }}" id="card-iyzico">
                <div class="pos-head">
                    <div class="pos-logo" style="background:#1E64FF;color:#fff">IY</div>
                    <div style="flex:1">
                        <div class="pos-name">iyzico</div>
                        <div class="pos-desc">Kredi kartı ile ödeme</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="hidden" name="iyzico_aktif" value="0">
                        <input type="checkbox" name="iyzico_aktif" value="1"
                               {{ $iyzicoAktif ? 'checked' : '' }}
                               onchange="document.getElementById('card-iyzico').classList.toggle('aktif', this.checked)">
                        <span class="knob"></span>
                    </label>
                </div>
                <div class="pos-body">
                    <div class="form-grid">
                        <div class="form-group full pw-toggle">
                            <label class="form-label">API Key</label>
                            <input type="password" id="iyzApi" name="iyzico_apikey"
                                   value="{{ old('iyzico_apikey', $ayarlar->iyzico_apikey ?? '') }}"
                                   class="form-input api-input">
                            <button type="button" class="toggle-btn" onclick="togglePw('iyzApi', this)">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                        <div class="form-group full pw-toggle">
                            <label class="form-label">Secret Key</label>
                            <input type="password" id="iyzSec" name="iyzico_secret"
                                   value="{{ old('iyzico_secret', $ayarlar->iyzico_secret ?? '') }}"
                                   class="form-input api-input">
                            <button type="button" class="toggle-btn" onclick="togglePw('iyzSec', this)">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Base URL</label>
                            <input type="text" name="iyzico_base"
                                   value="{{ old('iyzico_base', $ayarlar->iyzico_base ?? 'https://sandbox-api.iyzipay.com') }}"
                                   class="form-input api-input">
                            <small class="form-help">
                                Test: <code>https://sandbox-api.iyzipay.com</code> &middot;
                                Canlı: <code>https://api.iyzipay.com</code>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PAYTR --}}
            @php $paytrAktif = (int)($ayarlar->paytr_aktif ?? 0) === 1; $paytrTest = (int)($ayarlar->paytr_test_mode ?? 1) === 1; @endphp
            <div class="pos-card {{ $paytrAktif ? 'aktif' : '' }}" id="card-paytr">
                <div class="pos-head">
                    <div class="pos-logo" style="background:#FF6B35;color:#fff">PT</div>
                    <div style="flex:1">
                        <div class="pos-name">PayTR</div>
                        <div class="pos-desc">Türk ödeme altyapısı</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="hidden" name="paytr_aktif" value="0">
                        <input type="checkbox" name="paytr_aktif" value="1"
                               {{ $paytrAktif ? 'checked' : '' }}
                               onchange="document.getElementById('card-paytr').classList.toggle('aktif', this.checked)">
                        <span class="knob"></span>
                    </label>
                </div>
                <div class="pos-body">
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
                            <input type="password" id="ptKey" name="paytr_merchant_key"
                                   value="{{ old('paytr_merchant_key', $ayarlar->paytr_merchant_key ?? '') }}"
                                   class="form-input api-input">
                            <button type="button" class="toggle-btn" onclick="togglePw('ptKey', this)">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                        <div class="form-group full pw-toggle">
                            <label class="form-label">Merchant Salt</label>
                            <input type="password" id="ptSalt" name="paytr_merchant_salt"
                                   value="{{ old('paytr_merchant_salt', $ayarlar->paytr_merchant_salt ?? '') }}"
                                   class="form-input api-input">
                            <button type="button" class="toggle-btn" onclick="togglePw('ptSalt', this)">
                                <i data-lucide="eye" style="width:16px;height:16px"></i>
                            </button>
                        </div>
                        <div class="form-group full">
                            <div class="toggle-card" style="margin:0">
                                <div>
                                    <div class="lbl-strong">Test Modu</div>
                                    <div class="desc">Açıkken sandbox kullanılır (canlıya geçerken kapatın)</div>
                                </div>
                                <label class="ios-toggle">
                                    <input type="hidden" name="paytr_test_mode" value="0">
                                    <input type="checkbox" name="paytr_test_mode" value="1"
                                           {{ $paytrTest ? 'checked' : '' }}>
                                    <span class="knob"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- HAVALE / EFT --}}
            @php $havaleAktif = (int)($ayarlar->havale_aktif ?? 0) === 1; @endphp
            <div class="pos-card {{ $havaleAktif ? 'aktif' : '' }}" id="card-havale">
                <div class="pos-head">
                    <div class="pos-logo" style="background:#10b981;color:#fff">HV</div>
                    <div style="flex:1">
                        <div class="pos-name">Havale / EFT</div>
                        <div class="pos-desc">Manuel banka transferi (Banka Hesapları menüsünden yönetilir)</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="hidden" name="havale_aktif" value="0">
                        <input type="checkbox" name="havale_aktif" value="1"
                               {{ $havaleAktif ? 'checked' : '' }}
                               onchange="document.getElementById('card-havale').classList.toggle('aktif', this.checked)">
                        <span class="knob"></span>
                    </label>
                </div>
                <div class="pos-body">
                    <div style="display:flex;align-items:center;gap:8px;padding:12px;background:var(--bg-subtle);border-radius:var(--radius-md);font-size:13px">
                        <i data-lucide="info" style="width:16px;height:16px;color:var(--brand-dark)"></i>
                        <span>Havale banka hesaplarını <a href="{{ Route::has('admin.banka.index') ? route('admin.banka.index') : '#' }}" style="color:var(--brand-dark);font-weight:600">Banka Hesapları</a> menüsünden ekleyebilirsiniz.</span>
                    </div>
                </div>
            </div>

            {{-- BAKİYE İLE ÖDEME --}}
            @php $bakiyeAktif = (int)($ayarlar->bakiye_odeme_aktif ?? 0) === 1; @endphp
            <div class="pos-card {{ $bakiyeAktif ? 'aktif' : '' }}" id="card-bakiye">
                <div class="pos-head">
                    <div class="pos-logo" style="background:#a855f7;color:#fff">BK</div>
                    <div style="flex:1">
                        <div class="pos-name">Bakiye ile Ödeme</div>
                        <div class="pos-desc">Kullanıcı hesabındaki bakiyeden düşülerek ödeme</div>
                    </div>
                    <label class="ios-toggle">
                        <input type="hidden" name="bakiye_odeme_aktif" value="0">
                        <input type="checkbox" name="bakiye_odeme_aktif" value="1"
                               {{ $bakiyeAktif ? 'checked' : '' }}
                               onchange="document.getElementById('card-bakiye').classList.toggle('aktif', this.checked)">
                        <span class="knob"></span>
                    </label>
                </div>
            </div>

            <div class="sticky-save">
                <div style="font-size:13px;color:var(--text-muted)">
                    <i data-lucide="shield-check" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
                    Ödeme bilgileri SSL ile korunur
                </div>
                <button type="submit" class="btn btn-primary">
                    <i data-lucide="save"></i>
                    <span>Sanal POS Ayarlarını Kaydet</span>
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