@extends('admin._layout')

@section('title', 'Yeni Üye')

@push('head')
<style>
    .form-section {
        margin-bottom: 16px;
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
    .pwd-toggle-btn:hover {
        color: var(--brand-dark);
    }
    .pwd-toggle-btn i {
        width: 18px;
        height: 18px;
    }
</style>
@endpush

@section('content')

{{-- BREADCRUMB --}}
<div class="breadcrumb">
    <a href="{{ route('admin.dashboard') }}">Anasayfa</a>
    <span class="sep">/</span>
    <a href="{{ route('admin.uyeler.index') }}">Üyeler</a>
    <span class="sep">/</span>
    <span class="current">Yeni Üye</span>
</div>

{{-- PAGE HEADER --}}
<div class="page-header">
    <div>
        <h1 class="page-title">
            <i data-lucide="user-plus"></i>
            Yeni Üye Ekle
        </h1>
        <div class="page-subtitle">Sisteme yeni bir müşteri kaydı oluştur</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('admin.uyeler.index') }}" class="btn btn-ghost">
            <i data-lucide="arrow-left"></i>
            <span>Geri</span>
        </a>
    </div>
</div>

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
    <strong>Lütfen aşağıdaki hataları düzeltin:</strong>
    <ul style="margin:6px 0 0 18px">
        @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
        @endforeach
    </ul>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>
@endif

<form action="{{ route('admin.uyeler.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="form-grid">
        {{-- SOL KOLON --}}
        <div>
            {{-- KİŞİSEL BİLGİLER --}}
            <div class="section form-section">
                <div class="section-title">
                    <i data-lucide="user"></i>
                    <span>Kişisel Bilgiler</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Ad <span class="required">*</span></label>
                        <input type="text" name="ad" value="{{ old('ad') }}" required
                               class="form-input" placeholder="Ahmet">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Soyad <span class="required">*</span></label>
                        <input type="text" name="soyad" value="{{ old('soyad') }}" required
                               class="form-input" placeholder="Yılmaz">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-posta <span class="required">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="form-input" placeholder="ornek@mail.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="telefon" value="{{ old('telefon') }}"
                               class="form-input" placeholder="+90 555 123 45 67">
                    </div>
                    <div class="form-group">
                        <label class="form-label">TC Kimlik No</label>
                        <input type="text" name="tc" value="{{ old('tc') }}" maxlength="11"
                               class="form-input" placeholder="11 haneli">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Doğum Tarihi</label>
                        <input type="date" name="dtarih" value="{{ old('dtarih') }}" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cinsiyet</label>
                        <select name="cinsiyet" class="form-select">
                            <option value="Erkek" @if(old('cinsiyet', 'Erkek') === 'Erkek') selected @endif>Erkek</option>
                            <option value="Kadın" @if(old('cinsiyet') === 'Kadın') selected @endif>Kadın</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Üye Tipi</label>
                        <select name="utipi" class="form-select">
                            <option value="0" @if(old('utipi', '0') === '0') selected @endif>Bireysel</option>
                            <option value="1" @if(old('utipi') === '1') selected @endif>Kurumsal</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- FİRMA BİLGİLERİ --}}
            <div class="section form-section">
                <div class="section-title">
                    <i data-lucide="building-2"></i>
                    <span>Firma Bilgileri</span>
                    <span class="badge badge-neutral" style="font-weight:normal;margin-left:8px">Opsiyonel</span>
                </div>

                <div class="form-grid">
                    <div class="form-group full">
                        <label class="form-label">Firma Adı</label>
                        <input type="text" name="firmaadi" value="{{ old('firmaadi') }}"
                               class="form-input" placeholder="DN Kreatif Ltd. Şti.">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vergi No</label>
                        <input type="text" name="vergino" value="{{ old('vergino') }}"
                               class="form-input" placeholder="10 haneli">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vergi Dairesi</label>
                        <input type="text" name="vergidairesi" value="{{ old('vergidairesi') }}"
                               class="form-input" placeholder="Şişli">
                    </div>
                </div>
            </div>

            {{-- ADRES BİLGİLERİ --}}
            <div class="section form-section">
                <div class="section-title">
                    <i data-lucide="map-pin"></i>
                    <span>Adres Bilgileri</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">İl</label>
                        <input type="text" name="il" value="{{ old('il') }}"
                               class="form-input" placeholder="İstanbul">
                    </div>
                    <div class="form-group">
                        <label class="form-label">İlçe</label>
                        <input type="text" name="ilce" value="{{ old('ilce') }}"
                               class="form-input" placeholder="Şişli">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Posta Kodu</label>
                        <input type="text" name="pkodu" value="{{ old('pkodu') }}" maxlength="5"
                               class="form-input" placeholder="34000">
                    </div>
                    <div class="form-group full">
                        <label class="form-label">Açık Adres</label>
                        <textarea name="adres" rows="3" class="form-textarea"
                                  placeholder="Mahalle, sokak, bina no, daire...">{{ old('adres') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- SAĞ KOLON --}}
        <div>
            {{-- HESAP BİLGİLERİ --}}
            <div class="section form-section">
                <div class="section-title">
                    <i data-lucide="lock"></i>
                    <span>Hesap Bilgileri</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Şifre <span class="required">*</span></label>
                    <div class="pwd-toggle-wrap">
                        <input type="password" name="sifre" required minlength="6" id="sifre"
                               class="form-input" placeholder="En az 6 karakter"
                               style="padding-right:40px">
                        <button type="button" class="pwd-toggle-btn" onclick="pwdToggle('sifre', this)">
                            <i data-lucide="eye"></i>
                        </button>
                    </div>
                    <small class="form-help">Müşteri bu şifreyle giriş yapacak</small>
                </div>

               <div class="form-group">
                    <label class="form-label">Durum</label>
                    <select name="durum" class="form-select">
                        <option value="1" @if(old('durum', '1') === '1') selected @endif>Aktif</option>
                        <option value="0" @if(old('durum') === '0') selected @endif>Pasif</option>
                        <option value="2" @if(old('durum') === '2') selected @endif>Engelli</option>
                    </select>
                </div>

                <div class="form-group" style="margin-top:14px;padding:12px;background:var(--bg-subtle);border-radius:8px">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin:0">
                        <input type="checkbox" name="mail_gonder" value="1" checked>
                        <span style="font-size:13px"><strong>📧 Hoşgeldin maili gönder</strong></span>
                    </label>
                    <small class="form-help" style="margin-top:6px;display:block">Müşterinin e-postasına otomatik tanıtım maili atılır.</small>
                </div>
            </div>

            {{-- BİLGİ KARTI --}}
            <div class="section form-section" style="background:rgba(184,182,46,.05);border-color:var(--brand-soft)">
                <div style="display:flex;gap:12px;align-items:flex-start">
                    <i data-lucide="info" style="color:var(--brand-dark);flex-shrink:0;margin-top:2px"></i>
                    <div style="font-size:13px;line-height:1.6">
                        <strong>Bilgilendirme:</strong>
                        <ul style="margin:6px 0 0 18px;padding:0">
                            <li>Aynı e-posta ile birden fazla üye oluşturulamaz.</li>
                            <li>Şifre hash'lenerek kaydedilir, geri okunamaz.</li>
                            <li>Kayıt sonrası müşteri detay sayfasından şifre güncellenebilir.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- BUTONLAR --}}
    <div style="display:flex;justify-content:space-between;gap:12px;margin-top:20px;flex-wrap:wrap">
        <a href="{{ route('admin.uyeler.index') }}" class="btn btn-secondary">
            <i data-lucide="x"></i>
            <span>İptal</span>
        </a>
        <button type="submit" class="btn btn-primary">
            <i data-lucide="save"></i>
            <span>Üyeyi Kaydet</span>
        </button>
    </div>
</form>

<script>
function pwdToggle(inputId, btn){
    var inp = document.getElementById(inputId);
    if(!inp) return;
    if(inp.type === 'password'){
        inp.type = 'text';
        btn.innerHTML = '<i data-lucide="eye-off"></i>';
    } else {
        inp.type = 'password';
        btn.innerHTML = '<i data-lucide="eye"></i>';
    }
    if(window.lucide) window.lucide.createIcons();
}
</script>

@endsection