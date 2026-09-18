@extends('layouts.master')

@section('title', __('messages.register'))

@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/auth-page.css') }}">
<style>
/* İş ortağı (bayi) banner — kayıt formunun yanındaki boşluk */
.partner-banner{
    display:flex; gap:14px; align-items:flex-start;
    margin-top:26px; padding:18px 18px 16px;
    border:1px solid rgba(138,134,49,.28); border-radius:14px;
    background:linear-gradient(150deg, rgba(212,210,91,.20), rgba(255,255,255,.55));
    text-decoration:none; color:inherit;
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.partner-banner:hover{
    transform:translateY(-2px);
    border-color:rgba(138,134,49,.55);
    box-shadow:0 10px 26px rgba(138,134,49,.18);
    text-decoration:none; color:inherit;
}
.partner-banner__icon{
    flex-shrink:0; width:42px; height:42px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    background:#d4d25b; color:#2b2b1f; font-size:22px;
}
.partner-banner__tag{
    font-size:11px; font-weight:800; letter-spacing:.4px; text-transform:uppercase;
    color:#8a8631; margin-bottom:3px;
}
.partner-banner__title{ font-size:15.5px; font-weight:800; color:#1f2419; margin-bottom:4px; }
.partner-banner__desc{ font-size:13px; line-height:1.55; color:#5b6168; margin-bottom:8px; }
.partner-banner__cta{
    display:inline-flex; align-items:center; gap:5px;
    font-size:13px; font-weight:800; color:#8a6200;
}
.partner-banner:hover .partner-banner__cta i{ transform:translateX(3px); }
.partner-banner__cta i{ transition:transform .18s ease; }
@media (max-width:576px){
    .partner-banner{ padding:15px; gap:11px; }
    .partner-banner__icon{ width:36px; height:36px; font-size:19px; }
}
</style>
@endpush

@section('content')
<div class="top-header overlay" style="background-image: url({{ asset('tema/uploads/arkaplan/uyelik/bg.jpg') }}); background-size: cover; background-position: center;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper text-center">
                    <h1 class="heading">{{ __('messages.register') }}</h1>
                    <h3 class="subheading">{{ __('messages.create_new_account') ?? 'Yeni Hesap Oluştur' }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>
<section class="auth-page">
    <div class="container auth-container">
        <div class="auth-card">
            <div class="auth-left">
                <div class="eyebrow">{{ __('messages.new_membership') ?? 'Yeni Üyelik' }}</div>
                <h1>{{ $ayar->firma_adi ?? config('app.name') }}</h1>
                <p class="lead">
                    {{ __('messages.register_desc') ?? 'Tek panelden hizmetlerini yönet, ödemelerini takip et ve destek taleplerini oluştur.' }}
                </p>
                <ul class="bullet-list">
                    <li>{{ __('messages.quick_setup') ?? 'Hızlı kurulum ve yönetim' }}</li>
                    <li>{{ __('messages.notifications_tracking') ?? 'Güncel bildirimler ve ödeme takibi' }}</li>
                    <li>{{ __('messages.priority_support') ?? 'Öncelikli destek kanalları' }}</li>
                </ul>
                <div class="muted">
                    {{ __('messages.have_questions') ?? 'Soruların mı var?' }}
                    <a href="{{ localized_route('iletisim') }}">{{ __('messages.contact_support') }}</a>
                </div>

                {{-- İŞ ORTAĞI (BAYİ) BANNER — kayıt formunun yanındaki boşluk --}}
                @if(Route::has('isortagi.basvuru'))
                <a href="{{ localized_route('isortagi.basvuru') }}" class="partner-banner">
                    {{-- not: mdi-handshake bu MDI sürümünde YOK, mdi-store kullanılıyor --}}
                    <div class="partner-banner__icon"><i class="mdi mdi-store"></i></div>
                    <div class="partner-banner__body">
                        <div class="partner-banner__tag">İş Ortağı Programı</div>
                        <div class="partner-banner__title">Bayimiz olmak ister misiniz?</div>
                        <div class="partner-banner__desc">
                            Hizmetlerimizi kendi müşterilerinize sunun, her satıştan komisyon kazanın.
                        </div>
                        <span class="partner-banner__cta">Başvuru yap <i class="mdi mdi-arrow-right"></i></span>
                    </div>
                </a>
                @endif
            </div>
            <div class="auth-right">
                <div class="badge">{{ __('messages.register') }}</div>
                <h2>{{ __('messages.create_new_membership') ?? 'Yeni üyelik oluştur' }}</h2>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul style="margin:0; padding-left:18px;">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('kayit.post') }}" method="POST" class="form-grid">
                    @csrf
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><span class="icon"><i class="mdi mdi-account"></i></span>{{ __('messages.first_name') ?? 'Ad' }} *</label>
                            <input type="text" name="adi" class="form-input @error('adi') is-invalid @enderror" value="{{ old('adi') }}" required placeholder="{{ __('messages.first_name') ?? 'Ad' }}">
                            @error('adi')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label"><span class="icon"><i class="mdi mdi-account"></i></span>{{ __('messages.last_name') ?? 'Soyad' }} *</label>
                            <input type="text" name="soyadi" class="form-input @error('soyadi') is-invalid @enderror" value="{{ old('soyadi') }}" required placeholder="{{ __('messages.last_name') ?? 'Soyad' }}">
                            @error('soyadi')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><span class="icon"><i class="mdi mdi-email"></i></span>{{ __('messages.email_address') }} *</label>
                        <input type="email" name="email" class="form-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autocomplete="email" placeholder="ornek@mail.com">
                        @error('email')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label"><span class="icon"><i class="mdi mdi-calendar"></i></span>{{ __('messages.birth_date') }}</label>
                        <input type="date" name="dtarih" class="form-input @error('dtarih') is-invalid @enderror" value="{{ old('dtarih') }}" placeholder="GG.AA.YYYY">
                        @error('dtarih')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label"><span class="icon"><i class="mdi mdi-bullhorn"></i></span>Bizi Nereden Duydunuz?</label>
                        @php
                            $nerdenSecenekler = ['Google','Instagram','Facebook','X (Twitter)','Tavsiye / Arkadaş','Web Araması','Reklam','Diğer'];
                        @endphp
                        <select name="nereden_duydunuz" class="form-input @error('nereden_duydunuz') is-invalid @enderror">
                            <option value="">{{ __('messages.please_select') }}</option>
                            @foreach($nerdenSecenekler as $secenek)
                                <option value="{{ $secenek }}" {{ old('nereden_duydunuz') == $secenek ? 'selected' : '' }}>{{ $secenek }}</option>
                            @endforeach
                        </select>
                        @error('nereden_duydunuz')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label"><span class="icon"><i class="mdi mdi-phone"></i></span>{{ __('messages.phone') ?? 'Telefon' }}</label>
                        <input type="text" name="telefon" class="form-input @error('telefon') is-invalid @enderror" value="{{ old('telefon') }}" placeholder="05xx...">
                        @error('telefon')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><span class="icon"><i class="mdi mdi-lock"></i></span>{{ __('messages.password') }} *</label>
                            <input type="password" name="password" class="form-input @error('password') is-invalid @enderror" required autocomplete="new-password" placeholder="{{ __('messages.password_min') ?? 'Min. 6 karakter' }}">
                            @error('password')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label"><span class="icon"><i class="mdi mdi-lock"></i></span>{{ __('messages.password_confirmation') ?? 'Şifre Tekrar' }} *</label>
                            <input type="password" name="password_confirmation" class="form-input @error('password_confirmation') is-invalid @enderror" required autocomplete="new-password" placeholder="{{ __('messages.password_confirmation') ?? 'Şifre Tekrar' }}">
                            @error('password_confirmation')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="checkbox-row">
                        <input type="checkbox" id="hizmet_sozlesme" name="hizmet_sozlesme" value="1" {{ old('hizmet_sozlesme') ? 'checked' : '' }} required>
                        <label for="hizmet_sozlesme" class="muted">
                            <a class="muted-link" href="{{ url('/sayfa/uyelik-sozlesmesi') }}" target="_blank">{{ __('messages.membership_agreement') ?? 'Üyelik sözleşmesini' }}</a> {{ __('messages.read_and_accept') ?? 'okudum ve kabul ediyorum.' }} *
                        </label>
                    </div>
                    @error('hizmet_sozlesme')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror

                    <div class="checkbox-row">
                        <input type="checkbox" id="gizlilik_sozlesme" name="gizlilik_sozlesme" value="1" {{ old('gizlilik_sozlesme') ? 'checked' : '' }} required>
                        <label for="gizlilik_sozlesme" class="muted">
                            <a class="muted-link" href="{{ url('/sayfa/gizlilik-politikasi') }}" target="_blank">{{ __('messages.privacy_policy') }}</a>'nı okudum ve kabul ediyorum. *
                        </label>
                    </div>
                    @error('gizlilik_sozlesme')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror

                    <div class="checkbox-row">
                        <input type="checkbox" id="kvkk_onay" name="kvkk_onay" value="1" {{ old('kvkk_onay') ? 'checked' : '' }} required>
                        <label for="kvkk_onay" class="muted">
                            <a class="muted-link" href="{{ url('/sayfa/hizmet-ve-kullanim-sozlesmesi') }}" target="_blank">{{ __('messages.terms_of_use_agreement') }}</a>'ni okudum ve kabul ediyorum. *
                        </label>
                    </div>
                    @error('kvkk_onay')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror

                    <div id="passwordMismatch" style="display:none; color:#c33; font-size:13px; margin-bottom:10px;">
                        Girilen sifreler eslesmiyor.
                    </div>

                    <button type="submit" class="primary-btn" style="width:100%; justify-content:center;" id="registerBtn">
                        <i class="mdi mdi-account-plus"></i> {{ __('messages.register') }}
                    </button>

                    <div class="muted" style="margin-top:12px;">
                        {{ __('messages.already_have_account') ?? 'Zaten hesabınız var mı?' }} <a class="muted-link" href="{{ localized_route('giris') }}">{{ __('messages.login') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#registerBtn').closest('form');
    const pw = form.querySelector('input[name="password"]');
    const pwConfirm = form.querySelector('input[name="password_confirmation"]');
    const mismatchMsg = document.getElementById('passwordMismatch');
    const btn = document.getElementById('registerBtn');

    function checkMatch() {
        if (pwConfirm.value && pw.value !== pwConfirm.value) {
            mismatchMsg.style.display = 'block';
        } else {
            mismatchMsg.style.display = 'none';
        }
    }
    pw.addEventListener('input', checkMatch);
    pwConfirm.addEventListener('input', checkMatch);

    form.addEventListener('submit', function(e) {
        if (pw.value !== pwConfirm.value) {
            e.preventDefault();
            mismatchMsg.style.display = 'block';
            pwConfirm.focus();
            return false;
        }
        if (pw.value.length < 6) {
            e.preventDefault();
            alert('Sifre en az 6 karakter olmalidir.');
            pw.focus();
            return false;
        }
        btn.disabled = true;
        btn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Kayit yapiliyor...';
    });
});
</script>
@endpush
@endsection