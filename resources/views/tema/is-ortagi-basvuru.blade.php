@extends('layouts.master')

@section('title', 'İş Ortağı Başvurusu')

@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/auth-page.css') }}">
@endpush

@section('content')
<div class="top-header overlay" style="background-image: url({{ asset('tema/uploads/arkaplan/uyelik/bg.jpg') }}); background-size: cover; background-position: center;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper text-center">
                    <h1 class="heading">İş Ortağı Başvurusu</h1>
                    <h3 class="subheading">Bayimiz olun, birlikte büyüyelim</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="auth-page">
    <div class="container auth-container">
        <div class="auth-card">

            {{-- SOL: kısa bilgi --}}
            <div class="auth-left">
                <div class="eyebrow">İş Ortağı Programı</div>
                <h1>{{ $ayar->firma_adi ?? 'DN Kreatif İş Ortağım' }}</h1>
                <p class="lead">
                    Web sitesi, hosting ve domain hizmetlerimizi kendi müşterilerinize
                    sunun; her satıştan komisyon kazanın.
                </p>
                <ul class="bullet-list">
                    <li>Her satıştan komisyon kazancı</li>
                    <li>Size özel bayi paneli</li>
                    <li>Müşteri ve satış takibi</li>
                    <li>Başvuru tamamen ücretsiz</li>
                </ul>
                <div class="muted">
                    Başvurunuz onaylandığında <strong>giriş bilgileriniz e-posta ile gönderilir</strong>.
                    Bu formda şifre belirlemenize gerek yoktur.
                </div>
            </div>

            {{-- SAĞ: form --}}
            <div class="auth-right">
                <div class="badge">Başvuru</div>
                <h2>Başvuru formu</h2>

                @if(session('basvuru_ok'))
                    <div class="alert alert-success">
                        <strong>Başvurunuz alındı!</strong> Ekibimiz en kısa sürede
                        değerlendirip size dönüş yapacaktır. Bilgilendirme e-postası
                        adresinize gönderildi.
                    </div>
                @endif

                @if(session('zaten_var'))
                    <div class="alert alert-info">
                        Bu e-posta ile <strong>değerlendirme aşamasında olan bir başvurunuz</strong>
                        zaten var. Sonucu e-posta ile bildireceğiz.
                    </div>
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

                <form action="{{ route('isortagi.basvuru.kaydet') }}" method="POST" class="form-grid">
                    @csrf

                    {{-- bal küpü: botlar doldurur, gerçek kullanıcı görmez --}}
                    <input type="text" name="website" tabindex="-1" autocomplete="off"
                           style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0" aria-hidden="true">

                    <div class="form-group">
                        <label class="form-label"><span class="icon"><i class="mdi mdi-domain"></i></span>Firma / İşletme Adı *</label>
                        <input type="text" name="firma_adi" class="form-input @error('firma_adi') is-invalid @enderror"
                               value="{{ old('firma_adi') }}" required placeholder="Firma adınız">
                        @error('firma_adi')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label"><span class="icon"><i class="mdi mdi-account"></i></span>Yetkili Ad Soyad *</label>
                        <input type="text" name="ad_soyad" class="form-input @error('ad_soyad') is-invalid @enderror"
                               value="{{ old('ad_soyad') }}" required placeholder="Ad Soyad">
                        @error('ad_soyad')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><span class="icon"><i class="mdi mdi-email"></i></span>E-posta *</label>
                            <input type="email" name="email" class="form-input @error('email') is-invalid @enderror"
                                   value="{{ old('email') }}" required placeholder="ornek@mail.com">
                            @error('email')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label"><span class="icon"><i class="mdi mdi-phone"></i></span>Telefon *</label>
                            <input type="text" name="telefon" class="form-input @error('telefon') is-invalid @enderror"
                                   value="{{ old('telefon') }}" required placeholder="05xx xxx xx xx">
                            @error('telefon')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label"><span class="icon"><i class="mdi mdi-map-marker"></i></span>Şehir *</label>
                            <input type="text" name="il" class="form-input @error('il') is-invalid @enderror"
                                   value="{{ old('il') }}" required placeholder="İstanbul">
                            @error('il')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label"><span class="icon"><i class="mdi mdi-map-marker-outline"></i></span>İlçe</label>
                            <input type="text" name="ilce" class="form-input @error('ilce') is-invalid @enderror"
                                   value="{{ old('ilce') }}" placeholder="Kadıköy">
                            @error('ilce')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label"><span class="icon"><i class="mdi mdi-briefcase"></i></span>Faaliyet Alanınız</label>
                        @php
                            $faaliyetler = [
                                'Web Tasarım / Yazılım', 'Reklam / Ajans', 'Matbaa',
                                'Bilgisayar / Teknik Servis', 'Danışmanlık', 'Diğer',
                            ];
                        @endphp
                        <select name="faaliyet" class="form-input @error('faaliyet') is-invalid @enderror">
                            <option value="">Seçiniz</option>
                            @foreach($faaliyetler as $f)
                                <option value="{{ $f }}" {{ old('faaliyet') == $f ? 'selected' : '' }}>{{ $f }}</option>
                            @endforeach
                        </select>
                        @error('faaliyet')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label"><span class="icon"><i class="mdi mdi-message-text"></i></span>Mesajınız</label>
                        <textarea name="mesaj" rows="4" class="form-input @error('mesaj') is-invalid @enderror"
                                  placeholder="Eklemek istedikleriniz (isteğe bağlı)">{{ old('mesaj') }}</textarea>
                        @error('mesaj')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror
                    </div>

                    <div class="checkbox-row">
                        <input type="checkbox" id="kvkk" name="kvkk" value="1" {{ old('kvkk') ? 'checked' : '' }} required>
                        <label for="kvkk" class="muted">
                            <a class="muted-link" href="{{ url('/sayfa/gizlilik-politikasi') }}" target="_blank">Gizlilik Politikası</a>
                            ve
                            <a class="muted-link" href="{{ url('/sayfa/uyelik-sozlesmesi') }}" target="_blank">Kullanım Koşulları</a>'nı
                            okudum, kabul ediyorum. *
                        </label>
                    </div>
                    @error('kvkk')<div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>@enderror

                    <button type="submit" class="primary-btn" style="width:100%; justify-content:center;">
                        <i class="mdi mdi-send"></i> Başvuruyu Gönder
                    </button>

                    <div class="muted" style="text-align:center;margin-top:14px">
                        Zaten hesabınız var mı?
                        <a class="muted-link" href="{{ localized_route('giris') }}">Giriş yapın</a>
                    </div>
                </form>
            </div>

        </div>
    </div>
</section>
@endsection
