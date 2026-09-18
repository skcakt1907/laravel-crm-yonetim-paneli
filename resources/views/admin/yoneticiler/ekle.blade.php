@extends('admin._layout')

@section('title', 'Yeni Yönetici')

@push('head')
<style>
    .mode-tabs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 18px;
    }
    .mode-tab {
        position: relative;
        padding: 14px 16px;
        border: 2px solid var(--border);
        border-radius: var(--radius-md);
        cursor: pointer;
        transition: all .15s ease;
        background: var(--card-bg);
    }
    .mode-tab input[type=radio] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .mode-tab:hover {
        border-color: var(--brand);
    }
    .mode-tab.active {
        border-color: var(--brand);
        background: var(--brand-soft);
    }
    .mode-tab-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 4px;
    }
    .mode-tab-desc {
        font-size: 12px;
        color: var(--text-muted);
    }
    .pwd-toggle-wrap {
        position: relative;
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
        padding: 4px;
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.yoneticiler.index') }}">Yöneticiler</a>
    <span class="sep">/</span>
    <span class="current">Yeni</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="user-plus"></i>
            Yeni Yönetici
        </h1>
        <div class="page-subtitle">Mevcut bir üyeyi yönetici yap veya sıfırdan hesap oluştur</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.yoneticiler.index') }}" class="btn btn-ghost">
            <i data-lucide="arrow-left"></i>
            <span>Geri</span>
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <strong>Hata:</strong>
    <ul style="margin:6px 0 0 18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif

@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif

@if((int)session('admin_rol') !== 1)
<div class="alert alert-warning" style="margin-bottom:16px">
    <i data-lucide="alert-triangle" style="display:inline-block;vertical-align:-3px;margin-right:6px"></i>
    <strong>Uyarı:</strong> Sadece Patron rolündeki kullanıcı yeni yönetici ekleyebilir. Sayfayı görüntülüyorsunuz ama kayıt başarısız olacaktır.
</div>
@endif

@php $mode = old('account_mode', 'existing'); @endphp

<form action="{{ route('admin.yoneticiler.eklePost') }}" method="POST">
    @csrf

    <div class="form-grid">
        {{-- SOL --}}
        <div>
            {{-- HESAP KAYNAĞI --}}
            <div class="section" style="margin-bottom:16px">
                <div class="section-title">
                    <i data-lucide="user-cog"></i>
                    <span>Hesap Kaynağı</span>
                </div>

                <div class="mode-tabs">
                    <label class="mode-tab {{ $mode === 'existing' ? 'active' : '' }}" data-mode="existing">
                        <input type="radio" name="account_mode" value="existing" {{ $mode === 'existing' ? 'checked' : '' }}>
                        <div class="mode-tab-title">
                            <i data-lucide="users"></i>
                            <span>Mevcut Üyeden Seç</span>
                        </div>
                        <div class="mode-tab-desc">Var olan bir üyeyi yönetici olarak ekle</div>
                    </label>
                    <label class="mode-tab {{ $mode === 'new' ? 'active' : '' }}" data-mode="new">
                        <input type="radio" name="account_mode" value="new" {{ $mode === 'new' ? 'checked' : '' }}>
                        <div class="mode-tab-title">
                            <i data-lucide="user-plus"></i>
                            <span>Sıfırdan Yeni Hesap</span>
                        </div>
                        <div class="mode-tab-desc">Yeni üye + yönetici hesabı oluştur</div>
                    </label>
                </div>

                {{-- EXISTING MODE --}}
                <div id="mode-existing" style="display:{{ $mode === 'existing' ? 'block' : 'none' }}">
                    <div class="form-group">
                        <label class="form-label">Üye Seç <span class="required">*</span></label>
                        <select name="uye_id" class="form-select">
                            <option value="">— Üye seç —</option>
                            @foreach($uyeler as $u)
                                <option value="{{ $u->id }}" {{ old('uye_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->ad }} {{ $u->soyad }} — {{ $u->email }}
                                </option>
                            @endforeach
                        </select>
                        <small class="form-help">Son 500 üye listelenmektedir. Üye yoksa "Sıfırdan" moduna geç.</small>
                    </div>
                </div>

                {{-- NEW MODE --}}
                <div id="mode-new" style="display:{{ $mode === 'new' ? 'block' : 'none' }}">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Ad <span class="required">*</span></label>
                            <input type="text" name="uye_ad" value="{{ old('uye_ad') }}"
                                   class="form-input" placeholder="Ahmet">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Soyad <span class="required">*</span></label>
                            <input type="text" name="uye_soyad" value="{{ old('uye_soyad') }}"
                                   class="form-input" placeholder="Yılmaz">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">E-posta <span class="required">*</span></label>
                            <input type="email" name="uye_email" value="{{ old('uye_email') }}"
                                   class="form-input" placeholder="admin@ornek.com">
                        </div>
                        <div class="form-group full">
                            <label class="form-label">Telefon</label>
                            <input type="tel" name="uye_telefon" value="{{ old('uye_telefon') }}"
                                   class="form-input" placeholder="+90 555 123 45 67">
                        </div>
                    </div>
                </div>
            </div>

            {{-- YÖNETİCİ HESAP BİLGİLERİ --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="lock"></i>
                    <span>Yönetici Hesap Bilgileri</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Kullanıcı Adı <span class="required">*</span></label>
                        <input type="text" name="kullaniciadi" value="{{ old('kullaniciadi') }}" required
                               class="form-input" placeholder="benzersiz_kullanici_adi" maxlength="100">
                        <small class="form-help">Yönetici girişinde bu adla giriş yapılır</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Şifre <span class="required">*</span></label>
                        <div class="pwd-toggle-wrap">
                            <input type="password" name="sifre" required minlength="6" id="sifre1"
                                   class="form-input" placeholder="En az 6 karakter" style="padding-right:40px">
                            <button type="button" class="pwd-toggle-btn" onclick="pwdToggle('sifre1', this)">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Şifre Tekrar <span class="required">*</span></label>
                        <div class="pwd-toggle-wrap">
                            <input type="password" name="sifre_confirmation" required minlength="6" id="sifre2"
                                   class="form-input" placeholder="Aynı şifreyi tekrar gir" style="padding-right:40px">
                            <button type="button" class="pwd-toggle-btn" onclick="pwdToggle('sifre2', this)">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ --}}
        <div>
            {{-- ROL & YETKİ --}}
            <div class="section" style="margin-bottom:16px">
                <div class="section-title">
                    <i data-lucide="key-round"></i>
                    <span>Rol ve Yetki</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Rol <span class="required">*</span></label>
                    <select name="rol" required class="form-select">
                        <option value="">— Rol seç —</option>
                        @foreach($roller as $r)
                            <option value="{{ $r->id }}" {{ old('rol') == $r->id ? 'selected' : '' }}>
                                {{ $r->ikon ?? '🎭' }} {{ $r->ad }}
                                @if($r->aciklama) — {{ Str::limit($r->aciklama, 40) }} @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="form-help">Rol, yöneticinin erişebileceği sayfaları belirler</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Yetki Seviyesi</label>
                    <select name="yetki" class="form-select">
                        <option value="1" {{ old('yetki', '1') === '1' ? 'selected' : '' }}>1 — Standart</option>
                        <option value="2" {{ old('yetki') === '2' ? 'selected' : '' }}>2 — Gelişmiş</option>
                        <option value="3" {{ old('yetki') === '3' ? 'selected' : '' }}>3 — Tam</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
                        <input type="checkbox" name="durum" value="1" {{ old('durum', '1') ? 'checked' : '' }} style="width:18px;height:18px">
                        <div>
                            <div style="font-weight:600;font-size:14px">Hesap Aktif</div>
                            <div style="font-size:12px;color:var(--text-muted)">Yönetici hemen giriş yapabilsin</div>
                        </div>
                    </label>
                </div>
            </div>

            {{-- BİLGİ --}}
            <div class="section" style="background:rgba(184,182,46,.05);border-color:var(--brand-soft)">
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <i data-lucide="info" style="color:var(--brand-dark);flex-shrink:0;margin-top:2px"></i>
                    <div style="font-size:13px;line-height:1.6">
                        <strong>Önemli:</strong>
                        <ul style="margin:6px 0 0 18px;padding:0">
                            <li>Sadece <strong>Patron</strong> rolündeki kullanıcı rol atayabilir.</li>
                            <li>Kullanıcı adı benzersiz olmalıdır.</li>
                            <li>"Sıfırdan" modunda hem üye hem yönetici hesabı oluşturulur.</li>
                            <li>Şifre güçlü olmalı (en az 6, harf+rakam önerilir).</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BUTONLAR --}}
    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:20px;flex-wrap:wrap">
        <a href="{{ route('admin.yoneticiler.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Yöneticiyi Kaydet</span>
        </button>
    </div>
</form>

<script>
function pwdToggle(inputId, btn){
    var inp = document.getElementById(inputId);
    if(!inp) return;
    if(inp.type === 'password'){ inp.type = 'text'; btn.innerHTML = '<i data-lucide="eye-off"></i>'; }
    else { inp.type = 'password'; btn.innerHTML = '<i data-lucide="eye"></i>'; }
    if(window.lucide) window.lucide.createIcons();
}

// Mode tab toggle
document.querySelectorAll('.mode-tab').forEach(function(tab){
    tab.addEventListener('click', function(){
        var mode = this.dataset.mode;
        document.querySelectorAll('.mode-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        var radio = this.querySelector('input[type=radio]');
        if(radio) radio.checked = true;
        document.getElementById('mode-existing').style.display = (mode === 'existing') ? 'block' : 'none';
        document.getElementById('mode-new').style.display = (mode === 'new') ? 'block' : 'none';
    });
});
</script>

@endsection