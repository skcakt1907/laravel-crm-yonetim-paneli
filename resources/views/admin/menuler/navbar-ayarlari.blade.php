@extends('admin._layout')

@section('title', 'Navbar Üst Bar Ayarları')

@push('head')
<style>
    .menu-tabs {
        display: flex; gap: 4px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 20px;
    }
    .menu-tab {
        padding: 10px 16px;
        font-size: 13.5px;
        font-weight: 600;
        color: var(--text-muted);
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: -1px;
        transition: all 0.2s;
    }
    .menu-tab:hover { color: var(--text); }
    .menu-tab.active {
        color: var(--brand-dark);
        border-bottom-color: var(--brand);
    }
    body.theme-dark .menu-tab.active { color: var(--brand); }

    .button-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }
    .button-card.disabled {
        opacity: 0.55;
    }

    .button-card-head {
        display: flex;
        align-items: center;
        gap: 10px;
        justify-content: space-between;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border);
        margin-bottom: 12px;
    }
    .button-icon {
        width: 36px; height: 36px;
        border-radius: 8px;
        background: var(--brand-soft);
        color: var(--brand-dark);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    body.theme-dark .button-icon { color: var(--brand); }

    .ios-toggle { position: relative; display: inline-block; width: 48px; height: 26px; flex-shrink: 0; }
    .ios-toggle input { opacity: 0; width: 0; height: 0; }
    .ios-toggle .knob {
        position: absolute; cursor: pointer; inset: 0;
        background: var(--bg-subtle);
        border: 1px solid var(--border);
        border-radius: 26px;
        transition: 0.25s;
    }
    .ios-toggle .knob:before {
        position: absolute; content: ""; height: 18px; width: 18px;
        left: 3px; bottom: 3px; background: #fff;
        border-radius: 50%; transition: 0.25s;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .ios-toggle input:checked + .knob {
        background: linear-gradient(135deg, var(--brand), var(--brand-dark));
        border-color: var(--brand);
    }
    .ios-toggle input:checked + .knob:before { transform: translateX(22px); }

    .lang-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 10px;
    }
    @media (max-width: 768px) { .lang-grid { grid-template-columns: 1fr; } }

    .lang-input-wrap {
        position: relative;
    }
    .lang-input-wrap .lang-flag {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 18px;
        pointer-events: none;
    }
    .lang-input-wrap input {
        padding-left: 38px !important;
    }

    .sticky-save {
        position: sticky; bottom: 16px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius-md);
        padding: 12px 16px;
        margin-top: 20px;
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.08);
        z-index: 10;
    }

    .checkbox-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
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
        font-size: 13px;
        font-weight: 600;
        transition: all 0.15s;
    }
    .checkbox-pill input {
        margin: 0;
    }
    .checkbox-pill:hover { border-color: var(--brand-medium); }
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
    <span>Menüler</span>
    <span class="sep">/</span>
    <span class="current">Navbar Üst Bar Ayarları</span>
</div>

<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="settings"></i>
            Navbar Üst Bar Ayarları
        </h1>
        <div class="page-subtitle">Para birimi, dil seçici ve hesap butonlarını yönetin</div>
    </div>
</div>

{{-- ALT TAB --}}
<div class="menu-tabs">
    @if(Route::has('admin.menuler.header'))
        <a href="{{ route('admin.menuler.header') }}" class="menu-tab">
            <i data-lucide="menu" style="width:15px;height:15px"></i>
            <span>Navbar (Header)</span>
        </a>
    @endif
    @if(Route::has('admin.menuler.footer'))
        <a href="{{ route('admin.menuler.footer') }}" class="menu-tab">
            <i data-lucide="layout-template" style="width:15px;height:15px"></i>
            <span>Footer</span>
        </a>
    @endif
    <a href="{{ route('admin.menuler.navbar-ayarlari') }}" class="menu-tab active">
        <i data-lucide="settings" style="width:15px;height:15px"></i>
        <span>Navbar Üst Bar Ayarları</span>
    </a>
</div>

<form action="{{ route('admin.menuler.navbar-ayarlari.kaydet') }}" method="POST">
    @csrf

    {{-- DROPDOWN'LAR --}}
    <div class="section">
        <div class="section-title">
            <i data-lucide="chevron-down-square"></i>
            <span>Dropdown'lar</span>
        </div>

        @php
            $currencyOpt = $settings->currency_options ?? ['TRY','USD','EUR','AED'];
            $languageOpt = $settings->language_options ?? ['tr','en','ar'];
            $showCurrency = isset($settings->show_currency_dropdown) ? (int)$settings->show_currency_dropdown === 1 : true;
            $showLanguage = isset($settings->show_language_dropdown) ? (int)$settings->show_language_dropdown === 1 : true;
        @endphp

        {{-- PARA BİRİMİ --}}
        <div class="button-card {{ !$showCurrency ? 'disabled' : '' }}" id="currencyCard">
            <div class="button-card-head">
                <div style="display:flex;align-items:center;gap:12px;flex:1">
                    <div class="button-icon"><i data-lucide="dollar-sign"></i></div>
                    <div>
                        <div style="font-weight:600;font-size:14.5px">Para Birimi Seçici</div>
                        <div style="font-size:12px;color:var(--text-muted)">TRY/USD/EUR seçimi yapılır</div>
                    </div>
                </div>
                <label class="ios-toggle">
                    <input type="checkbox" name="show_currency_dropdown" value="1"
                           {{ $showCurrency ? 'checked' : '' }}
                           onchange="document.getElementById('currencyCard').classList.toggle('disabled', !this.checked)">
                    <span class="knob"></span>
                </label>
            </div>

            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:12px">Aktif Para Birimleri</label>
                <div class="checkbox-pills">
                    @foreach(['TRY','USD','EUR','GBP','AED','SAR'] as $cur)
                        <label class="checkbox-pill {{ in_array($cur, $currencyOpt) ? 'checked' : '' }}">
                            <input type="checkbox" name="currency_options[]" value="{{ $cur }}"
                                   {{ in_array($cur, $currencyOpt) ? 'checked' : '' }}
                                   onchange="this.parentElement.classList.toggle('checked', this.checked)">
                            <span>{{ $cur }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- DİL --}}
        <div class="button-card {{ !$showLanguage ? 'disabled' : '' }}" id="languageCard">
            <div class="button-card-head">
                <div style="display:flex;align-items:center;gap:12px;flex:1">
                    <div class="button-icon"><i data-lucide="languages"></i></div>
                    <div>
                        <div style="font-weight:600;font-size:14.5px">Dil Seçici</div>
                        <div style="font-size:12px;color:var(--text-muted)">TR/EN/AR dil değişimi</div>
                    </div>
                </div>
                <label class="ios-toggle">
                    <input type="checkbox" name="show_language_dropdown" value="1"
                           {{ $showLanguage ? 'checked' : '' }}
                           onchange="document.getElementById('languageCard').classList.toggle('disabled', !this.checked)">
                    <span class="knob"></span>
                </label>
            </div>

            <div class="form-group" style="margin:0">
                <label class="form-label" style="font-size:12px">Aktif Diller</label>
                <div class="checkbox-pills">
                    @foreach([['tr','🇹🇷','Türkçe'],['en','🇬🇧','English'],['ar','🇸🇦','العربية'],['de','🇩🇪','Deutsch'],['fr','🇫🇷','Français'],['es','🇪🇸','Español']] as $lang)
                        <label class="checkbox-pill {{ in_array($lang[0], $languageOpt) ? 'checked' : '' }}">
                            <input type="checkbox" name="language_options[]" value="{{ $lang[0] }}"
                                   {{ in_array($lang[0], $languageOpt) ? 'checked' : '' }}
                                   onchange="this.parentElement.classList.toggle('checked', this.checked)">
                            <span>{{ $lang[1] }} {{ $lang[2] }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- BUTONLAR --}}
    @php
        $buttons = [
            ['key' => 'login',    'icon' => 'log-in',     'name' => 'Giriş Yap Butonu',  'default_url' => '/giris'],
            ['key' => 'register', 'icon' => 'user-plus',  'name' => 'Kayıt Ol Butonu',   'default_url' => '/kayit'],
            ['key' => 'account',  'icon' => 'user',       'name' => 'Hesabım Butonu',    'default_url' => '/hesabim'],
            ['key' => 'cart',     'icon' => 'shopping-cart','name' => 'Sepet Butonu',    'default_url' => '/sepet'],
            ['key' => 'logout',   'icon' => 'log-out',    'name' => 'Çıkış Yap Butonu',  'default_url' => '/cikis'],
        ];
    @endphp

    <div class="section" style="margin-top:16px">
        <div class="section-title">
            <i data-lucide="square-mouse-pointer"></i>
            <span>Hesap Butonları</span>
        </div>

        @foreach($buttons as $btn)
            @php
                $showField = 'show_' . $btn['key'] . '_button';
                $urlField  = $btn['key'] . '_button_url';
                $trField   = $btn['key'] . '_button_text_tr';
                $enField   = $btn['key'] . '_button_text_en';
                $arField   = $btn['key'] . '_button_text_ar';
                $show = isset($settings->$showField) ? (int)$settings->$showField === 1 : true;
            @endphp

            <div class="button-card {{ !$show ? 'disabled' : '' }}" id="card-{{ $btn['key'] }}">
                <div class="button-card-head">
                    <div style="display:flex;align-items:center;gap:12px;flex:1">
                        <div class="button-icon"><i data-lucide="{{ $btn['icon'] }}"></i></div>
                        <div>
                            <div style="font-weight:600;font-size:14.5px">{{ $btn['name'] }}</div>
                            <div style="font-size:12px;color:var(--text-muted)">3 dilde metin + yönlendirme</div>
                        </div>
                    </div>
                    <label class="ios-toggle">
                        <input type="checkbox" name="{{ $showField }}" value="1"
                               {{ $show ? 'checked' : '' }}
                               onchange="document.getElementById('card-{{ $btn['key'] }}').classList.toggle('disabled', !this.checked)">
                        <span class="knob"></span>
                    </label>
                </div>

                <div class="form-grid" style="margin-bottom:12px">
                    <div class="form-group full">
                        <label class="form-label" style="font-size:12px">Yönlendirme URL</label>
                        <input type="text" name="{{ $urlField }}"
                               value="{{ old($urlField, $settings->$urlField ?? $btn['default_url']) }}"
                               class="form-input"
                               placeholder="{{ $btn['default_url'] }}"
                               style="font-family:monospace;font-size:13px">
                    </div>
                </div>

                <div>
                    <label class="form-label" style="font-size:12px">Buton Metinleri</label>
                    <div class="lang-grid">
                        <div class="lang-input-wrap">
                            <span class="lang-flag">🇹🇷</span>
                            <input type="text" name="{{ $trField }}"
                                   value="{{ old($trField, $settings->$trField ?? '') }}"
                                   class="form-input" placeholder="Türkçe metin">
                        </div>
                        <div class="lang-input-wrap">
                            <span class="lang-flag">🇬🇧</span>
                            <input type="text" name="{{ $enField }}"
                                   value="{{ old($enField, $settings->$enField ?? '') }}"
                                   class="form-input" placeholder="English text">
                        </div>
                        <div class="lang-input-wrap">
                            <span class="lang-flag">🇸🇦</span>
                            <input type="text" name="{{ $arField }}"
                                   value="{{ old($arField, $settings->$arField ?? '') }}"
                                   class="form-input" placeholder="نص عربي" dir="rtl">
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="sticky-save">
        <div style="font-size:13px;color:var(--text-muted)">
            <i data-lucide="info" style="width:13px;height:13px;display:inline;vertical-align:middle"></i>
            Tüm değişiklikler kaydet butonu ile uygulanır
        </div>
        <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-primary">
                <i data-lucide="save"></i>
                <span>Ayarları Kaydet</span>
            </button>
        </div>
    </div>
</form>

@endsection