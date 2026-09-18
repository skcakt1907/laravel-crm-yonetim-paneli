@extends('admin._layout')

@section('title', 'Yöneticiyi Düzenle')

@push('head')
<style>
    .pwd-toggle-wrap { position: relative; }
    .pwd-toggle-btn {
        position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
        background: transparent; border: none; cursor: pointer;
        color: var(--text-muted); padding: 4px;
    }
</style>
@endpush

@section('content')

@php
    $isim = $yonetici->adi ?? $yonetici->kullaniciadi ?? '—';
    $bas = strtoupper(mb_substr($isim, 0, 1));
@endphp

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.yoneticiler.index') }}">Yöneticiler</a>
    <span class="sep">/</span>
    <span class="current">{{ $isim }}</span>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <ul style="margin:0 0 0 18px">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
</div>
@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif
@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif

{{-- PROFILE HEADER --}}
<div class="profile-header">
    <div class="profile-avatar"
         style="background:linear-gradient(135deg, var(--brand), var(--brand-dark));font-size:28px;color:#fff;overflow:hidden">
        @if(!empty($yonetici->profil_foto ?? null) && is_file(public_path($yonetici->profil_foto)))
            <img src="{{ asset($yonetici->profil_foto) }}?v={{ time() }}" alt="" style="width:100%;height:100%;object-fit:cover">
        @else
            {{ $bas }}
        @endif
    </div>
    <div class="profile-info">
        <h2 class="profile-name">{{ $isim }}</h2>
        <div class="meta-row" style="margin-top:4px">
            <span><i data-lucide="at-sign" style="width:14px;height:14px"></i> {{ $yonetici->kullaniciadi }}</span>
            @if($yonetici->email)
                <span><i data-lucide="mail" style="width:14px;height:14px"></i> {{ $yonetici->email }}</span>
            @endif
            <span><i data-lucide="hash" style="width:14px;height:14px"></i> #{{ $yonetici->id }}</span>
        </div>
        <div style="margin-top:10px;display:flex;gap:8px;flex-wrap:wrap">
            @if((int)($yonetici->durum ?? 0) === 1)
                <span class="badge badge-success">Aktif</span>
            @else
                <span class="badge badge-danger">Pasif</span>
            @endif
        </div>
    </div>
    <div class="profile-actions">
        <a href="{{ route('admin.yoneticiler.index') }}" class="btn btn-ghost btn-sm">
            <i data-lucide="arrow-left"></i>
            <span>Geri</span>
        </a>
    </div>
</div>

@if((int)session('admin_rol') !== 1)
<div class="alert alert-warning" style="margin-top:16px">
    <i data-lucide="alert-triangle" style="display:inline-block;vertical-align:-3px;margin-right:6px"></i>
    Sadece <strong>Patron</strong> rolündeki kullanıcı yönetici düzenleyebilir.
</div>
@endif

<form action="{{ route('admin.yoneticiler.duzenlePost', $yonetici->id) }}" method="POST" enctype="multipart/form-data" style="margin-top:20px">
    @csrf

    <div class="form-grid">
        {{-- SOL: Hesap Bilgileri --}}
        <div>
            <div class="section" style="margin-bottom:16px">
                <div class="section-title">
                    <i data-lucide="user"></i>
                    <span>Hesap Bilgileri</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Kullanıcı Adı <span class="required">*</span></label>
                        <input type="text" name="kullaniciadi" value="{{ old('kullaniciadi', $yonetici->kullaniciadi) }}"
                               required class="form-input" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Görünen Ad</label>
                        <input type="text" name="adi" value="{{ old('adi', $yonetici->adi) }}"
                               class="form-input" placeholder="Ahmet Yılmaz">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-posta <span class="required">*</span></label>
                        <input type="email" name="email"
                               value="{{ old('email', $yonetici->email ?? $yonetici->eposta ?? '') }}"
                               required class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="telefon" value="{{ old('telefon', $yonetici->telefon) }}"
                               class="form-input" placeholder="+90 555 123 45 67">
                    </div>
                </div>

                @php $fotoVar = !empty($yonetici->profil_foto ?? null) && is_file(public_path($yonetici->profil_foto)); @endphp
                <div class="form-group" style="margin-top:6px;margin-bottom:0">
                    <label class="form-label">Profil Fotoğrafı</label>
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                        @if($fotoVar)
                            <img src="{{ asset($yonetici->profil_foto) }}?v={{ time() }}" alt=""
                                 style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--border);flex-shrink:0">
                        @endif
                        <input type="file" name="profil_foto" accept=".jpg,.jpeg,.png,.webp" class="form-input" style="max-width:320px">
                        @if($fotoVar)
                            <label style="display:inline-flex;align-items:center;gap:6px;font-size:12.5px;color:var(--danger);cursor:pointer">
                                <input type="checkbox" name="foto_kaldir" value="1" style="width:auto;margin:0">
                                <span>Fotoğrafı kaldır</span>
                            </label>
                        @endif
                    </div>
                    <small class="form-help">JPG, PNG veya WEBP — en fazla 2MB</small>
                </div>
            </div>

            {{-- ŞİFRE --}}
            <div class="section">
                <div class="section-title">
                    <i data-lucide="key"></i>
                    <span>Şifre Değiştir</span>
                    <span class="badge badge-neutral" style="font-weight:normal;margin-left:8px">Opsiyonel</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Yeni Şifre</label>
                    <div class="pwd-toggle-wrap">
                        <input type="password" name="sifre" id="sifre"
                               class="form-input" placeholder="Boş bırak — değiştirme"
                               style="padding-right:40px" minlength="6">
                        <button type="button" class="pwd-toggle-btn" onclick="pwdToggle('sifre', this)">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                    <small class="form-help">Boş bırakırsanız mevcut şifre korunur</small>
                </div>
            </div>
        </div>

        {{-- SAĞ: Rol & Durum --}}
        <div>
            <div class="section" style="margin-bottom:16px">
                <div class="section-title">
                    <i data-lucide="key-round"></i>
                    <span>Rol ve Yetki</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Rol <span class="required">*</span></label>
                    <select name="rol" required class="form-select">
                        @foreach($roller as $r)
                            <option value="{{ $r->id }}" {{ old('rol', $yonetici->rol) == $r->id ? 'selected' : '' }}>
                                {{ $r->ikon ?? '🎭' }} {{ $r->ad }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Yetki Seviyesi</label>
                    <select name="yetki" class="form-select">
                        <option value="1" {{ old('yetki', $yonetici->yetki ?? 1) == 1 ? 'selected' : '' }}>1 — Standart</option>
                        <option value="2" {{ old('yetki', $yonetici->yetki ?? 1) == 2 ? 'selected' : '' }}>2 — Gelişmiş</option>
                        <option value="3" {{ old('yetki', $yonetici->yetki ?? 1) == 3 ? 'selected' : '' }}>3 — Tam</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Durum</label>
                    <select name="durum" class="form-select">
                        <option value="1" {{ old('durum', $yonetici->durum ?? 1) == 1 ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ old('durum', $yonetici->durum ?? 1) == 0 ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>
            </div>

            <div class="section" style="background:rgba(184,182,46,.05);border-color:var(--brand-soft)">
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <i data-lucide="info" style="color:var(--brand-dark);flex-shrink:0;margin-top:2px"></i>
                    <div style="font-size:13px;line-height:1.6">
                        Rol ataması, kullanıcının erişebileceği <strong>sayfaları</strong> belirler.
                        Detaylı yetki yönetimi için
                        <a href="{{ route('admin.roller.index') }}" style="color:var(--brand-dark);font-weight:600">
                            Roller
                        </a>
                        sayfasına gidin.
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
            <span>Değişiklikleri Kaydet</span>
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
</script>

@endsection