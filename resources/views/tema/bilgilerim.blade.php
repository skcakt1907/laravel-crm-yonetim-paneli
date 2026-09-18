@extends('layouts.panel')

@section('page_title', 'Bilgilerim')

@section('panel_content')
@php $u = Auth::guard('uye')->user(); @endphp

{{-- Bildirimler site geneli tek popup ile gösteriliyor: _partials/flash-toast --}}

<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-user"></i>
            {{ __('messages.my_profile_info') }}
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            Bilgilerim
        </div>
    </div>

    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item col-6 col-sm-6 col-md-3">
            <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab">
                <i class="fas fa-info"></i> {{ __('messages.summary') }}
            </a>
        </li>
        <li class="nav-item col-6 col-sm-6 col-md-3">
            <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab">
                <i class="fas fa-file-invoice"></i> {{ __('messages.invoice_info_badge') }}
            </a>
        </li>
        <li class="nav-item col-6 col-sm-6 col-md-3">
            <a class="nav-link" id="contact-tab" data-toggle="tab" href="#contact" role="tab">
                <i class="fas fa-user-check"></i> Tercihler
            </a>
        </li>
        <li class="nav-item col-6 col-sm-6 col-md-3">
            <a class="nav-link" id="password-tab" data-toggle="tab" href="#password" role="tab">
                <i class="fas fa-key"></i> {{ __('messages.change_password_tab') }}
            </a>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
        {{-- ÖZET --}}
        <div class="tab-pane fade show active pt-3" id="home" role="tabpanel">
            <form method="POST" action="{{ route('bilgilerim.post') }}" autocomplete="off" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="tab" value="ozet">

                <div class="row">
                    <div class="col-md-6 col-xs-12">
                        <div class="hesap_bilgi badge bg-pink mb-4">{{ __('messages.account_info') }}</div>
                        <div class="clear"></div>

                        @php $fotoVar = !empty($u->profil_foto ?? null) && is_file(public_path($u->profil_foto)); @endphp
                        <label>{{ __('messages.profile_photo') }}</label>
                        <div class="mb-4">
                            @if($fotoVar)
                                <img src="{{ asset($u->profil_foto) }}?v={{ time() }}" alt="{{ __('messages.profile_photo_alt') }}"
                                     style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:2px solid #e5e7eb;display:block;margin-bottom:8px">
                            @endif
                            <input type="file" name="profil_foto" accept=".jpg,.jpeg,.png,.webp" class="form-control">
                            @if($fotoVar)
                                <label style="font-weight:400;font-size:13px;margin-top:6px;display:block;cursor:pointer">
                                    <input type="checkbox" name="foto_kaldir" value="1"> {{ __('messages.remove_photo') }}
                                </label>
                            @endif
                            <small class="form-text text-muted">JPG, PNG veya WEBP — en fazla 2MB</small>
                        </div>

                        <label>{{ __('messages.account_type') }}</label>
                        <div class="cd-filter-block mb-0">
                            <ul class="radio-group radios-filter cd-filter-content list mb-0">
                                <li class="mb-0">
                                    <input value="0" type="radio" name="utipi" id="radio6" {{ $u->utipi == 0 ? 'checked' : '' }}>
                                    <label class="radio-label" for="radio6">Bireysel</label>
                                </li>
                                <li class="mb-0">
                                    <input value="1" type="radio" name="utipi" id="radio7" {{ $u->utipi == 1 ? 'checked' : '' }}>
                                    <label class="radio-label" for="radio7">Kurumsal</label>
                                </li>
                            </ul>
                        </div>

                        <div class="form-group">
                            <label for="kullanici_adi">{{ __('messages.username') }} <span class="text-danger">*</span></label>
                            <input type="text" name="kullanici_adi" id="kullanici_adi" class="form-control" value="{{ old('kullanici_adi', $u->kullanici_adi ?? '') }}" placeholder="{{ __('messages.username') }}" minlength="3" maxlength="50" required>
                            <small class="form-text text-muted">{{ __('messages.username_hint') }}</small>
                            @error('kullanici_adi')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label for="ad">Ad <span class="text-danger">*</span></label>
                            <input type="text" name="ad" id="ad" class="form-control" value="{{ old('ad', $u->ad) }}" placeholder="Ad" required>
                            @error('ad')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mt-4">
                            <label for="soyad">Soyad <span class="text-danger">*</span></label>
                            <input type="text" name="soyad" id="soyad" class="form-control" value="{{ old('soyad', $u->soyad) }}" placeholder="Soyad" required>
                            @error('soyad')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mt-4">
                            <label for="email">E-posta <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $u->email) }}" placeholder="E-posta" required>
                            @error('email')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mt-4">
                            <label for="telefon">Telefon <span class="text-danger">*</span></label>
                            <input type="text" name="telefon" id="telefon" class="form-control telefonmask" value="{{ old('telefon', $u->telefon) }}" placeholder="Telefon" required>
                            @error('telefon')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group mt-4">
                            <label for="tc">TC Kimlik No <span class="text-danger" id="tcReqStar" style="{{ $u->utipi == 0 ? '' : 'display:none' }}">*</span></label>
                            <input type="text" name="tc" id="tc" class="form-control" maxlength="11" inputmode="numeric" value="{{ old('tc', $u->tc ?? '') }}" placeholder="TC Kimlik No" {{ $u->utipi == 0 ? 'required' : '' }}>
                            @error('tc')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <script>
                        (function(){
                            var tc=document.getElementById('tc'), star=document.getElementById('tcReqStar');
                            document.querySelectorAll('input[name="utipi"]').forEach(function(r){
                                r.addEventListener('change', function(){
                                    var sel=document.querySelector('input[name="utipi"]:checked');
                                    var bireysel = sel && sel.value === '0';
                                    if(tc) tc.required = bireysel;
                                    if(star) star.style.display = bireysel ? '' : 'none';
                                });
                            });
                        })();
                        </script>
                    </div>

                    <div class="col-md-6 col-xs-12">
                        <div class="hesap_bilgi badge bg-pink mb-4">{{ __('messages.address_info') }}</div>
                        <div class="clear"></div>

                        <div class="form-group">
                            <label for="ulke">{{ __('messages.country') }}</label>
                            <input type="text" name="ulke" id="ulke" class="form-control" value="{{ old('ulke', $u->ulke ?? '') }}" placeholder="{{ __('messages.country') }}">
                        </div>
                        <div class="form-group mt-4">
                            <label for="sehir">{{ __('messages.city') }}</label>
                            <input type="text" name="sehir" id="sehir" class="form-control" value="{{ old('sehir', $u->sehir ?? '') }}" placeholder="{{ __('messages.city') }}">
                        </div>
                        <div class="form-group mt-4">
                            <label for="adres">{{ __('messages.address') }}</label>
                            <textarea name="adres" id="adres" class="form-control" rows="6" placeholder="{{ __('messages.address') }}">{{ old('adres', $u->adres ?? '') }}</textarea>
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-success pull-right">
                            <i class="fa fa-save"></i> {{ __('messages.update_button') }}
                        </button>
                        <div class="clear"></div>
                    </div>
                </div>
            </form>
        </div>

        {{-- FATURA --}}
        <div class="tab-pane fade pt-3" id="profile" role="tabpanel">
            <form method="POST" action="{{ route('bilgilerim.post') }}" autocomplete="off">
                @csrf
                <input type="hidden" name="tab" value="fatura">

                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="fatura_unvan">{{ __('messages.invoice_title') }}</label>
                        <input type="text" name="fatura_unvan" id="fatura_unvan" class="form-control" value="{{ old('fatura_unvan', $u->fatura_unvan ?? '') }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fatura_tc">TC Kimlik / Vergi No</label>
                        <input type="text" name="fatura_tc" id="fatura_tc" class="form-control" value="{{ old('fatura_tc', $u->fatura_tc ?? '') }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fatura_sehir">{{ __('messages.city') }}</label>
                        <input type="text" name="fatura_sehir" id="fatura_sehir" class="form-control" value="{{ old('fatura_sehir', $u->fatura_sehir ?? '') }}">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fatura_ulke">{{ __('messages.country') }}</label>
                        <input type="text" name="fatura_ulke" id="fatura_ulke" class="form-control" value="{{ old('fatura_ulke', $u->fatura_ulke ?? '') }}">
                    </div>
                    <div class="form-group col-12">
                        <label for="fatura_adres">{{ __('messages.invoice_address') }}</label>
                        <textarea name="fatura_adres" id="fatura_adres" class="form-control" rows="4">{{ old('fatura_adres', $u->fatura_adres ?? '') }}</textarea>
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" class="btn btn-success pull-right">
                            <i class="fa fa-save"></i> {{ __('messages.update_button') }}
                        </button>
                        <div class="clear"></div>
                    </div>
                </div>
            </form>
        </div>

        {{-- TERCİHLER --}}
        <div class="tab-pane fade pt-3" id="contact" role="tabpanel">
            <form method="POST" action="{{ route('bilgilerim.post') }}" autocomplete="off">
                @csrf
                <input type="hidden" name="tab" value="tercihler">

                <table class="table table-bordered mt-3">
                    <tbody>
                    <tr>
                        <td>E-posta Bildirimleri</td>
                        <td>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="email_bildirimleri" name="email_bildirimleri" value="1" {{ ($u->email_bildirimleri ?? 0) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="email_bildirimleri">{{ __('messages.active') }}</label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>SMS Bildirimleri</td>
                        <td>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="sms_bildirimleri" name="sms_bildirimleri" value="1" {{ ($u->sms_bildirimleri ?? 0) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="sms_bildirimleri">{{ __('messages.active') }}</label>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <button type="submit" class="btn btn-primary pull-right">
                    <i class="fa fa-save"></i> {{ __('messages.update_button') }}
                </button>
                <div class="clear"></div>
            </form>
        </div>

        {{-- ŞİFRE --}}
        <div class="tab-pane fade pt-3" id="password" role="tabpanel">
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> {{ __('messages.password_change_verify_hint') }}
            </div>

            <form method="POST" action="{{ route('bilgilerim.post') }}" id="sifreDegistirForm" autocomplete="off">
                @csrf
                <input type="hidden" name="tab" value="sifre">

                <div class="form-group">
                    <label>{{ __('messages.verification_method') }}</label>
                    <div>
                        <label class="mr-3">
                            <input type="radio" name="verification_type" value="email" checked> E-posta
                        </label>
                        <label>
                            <input type="radio" name="verification_type" value="telefon"> Telefon
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="verification_code">{{ __('messages.verification_code_label') }}</label>
                    <div class="input-group">
                        <input type="text" name="verification_code" id="verification_code" class="form-control" placeholder="6 haneli kod" maxlength="6" style="text-align:center;font-size:18px;letter-spacing:6px">
                        <div class="input-group-append">
                            <button type="button" id="kodGonderBtn" onclick="kodGonder()" class="btn btn-outline-primary">
                                <i class="fa fa-paper-plane"></i> {{ __('messages.send_code') }}
                            </button>
                        </div>
                    </div>
                    <small id="kodMesaj" class="form-text"></small>
                </div>

                <div class="form-group">
                    <label for="new_password">{{ __('messages.new_password_label') }} <span class="text-danger">*</span></label>
                    <div style="position:relative;">
                        <input class="form-control" type="password" id="new_password" name="new_password" placeholder="******" minlength="6" required style="padding-right:42px;">
                        <button type="button" onclick="toggleSifre('new_password','iconNewPass')" tabindex="-1" style="position:absolute;top:50%;right:10px;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:4px;display:flex;align-items:center;">
                            <i class="mdi mdi-eye" id="iconNewPass" style="font-size:20px;"></i>
                        </button>
                    </div>
                    @error('new_password')<div class="text-danger">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label for="new_password_confirmation">{{ __('messages.new_password_confirm_label') }} <span class="text-danger">*</span></label>
                    <div style="position:relative;">
                        <input class="form-control" type="password" id="new_password_confirmation" name="new_password_confirmation" placeholder="******" minlength="6" required style="padding-right:42px;">
                        <button type="button" onclick="toggleSifre('new_password_confirmation','iconNewPass2')" tabindex="-1" style="position:absolute;top:50%;right:10px;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:4px;display:flex;align-items:center;">
                            <i class="mdi mdi-eye" id="iconNewPass2" style="font-size:20px;"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary pull-right">
                    <i class="fa fa-key"></i> {{ __('messages.change_password_button') }}
                </button>
                <div class="clear"></div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Profil eksik uyarısı geldiğinde: kurumsal ise Fatura sekmesini, bireysel ise Genel sekmeyi öne çıkar
@if(session('profil_eksik'))
(function () {
    var utipi = {{ (int) (Auth::guard('uye')->user()->utipi ?? 0) }};

    // Doğru sekmeyi aç + ilk boş alana odaklan (banner artık HTML olarak yukarıda render ediliyor)
    var hedefTab = (utipi === 1) ? 'profile-tab' : 'home-tab'; // profile=Fatura, home=Genel
    var el = document.getElementById(hedefTab);
    if (el) {
        try { el.click(); } catch (e) {}
        setTimeout(function () {
            var pane = (utipi === 1) ? document.getElementById('profile') : document.getElementById('home');
            if (pane) {
                var bosInput = pane.querySelector('input[value=""], textarea:empty, input:not([value])');
                if (bosInput) { try { bosInput.focus(); } catch (e) {} }
                pane.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 300);
    }
})();
@endif
function toggleSifre(inputId, iconId) {
    var inp = document.getElementById(inputId);
    var icon = document.getElementById(iconId);
    if (!inp) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        if (icon) icon.className = 'mdi mdi-eye-off';
    } else {
        inp.type = 'password';
        if (icon) icon.className = 'mdi mdi-eye';
    }
}
function kodGonder(){
    var t = document.querySelector('input[name="verification_type"]:checked').value;
    var btn = document.getElementById('kodGonderBtn');
    var m = document.getElementById('kodMesaj');
    btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Gönderiliyor...'; m.textContent = '';
    fetch('{{ route("sifre.degistir.kod.gonder") }}', {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({verification_type: t})
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if(d.success){ m.style.color='#28a745'; m.textContent = d.message; btn.innerHTML='<i class="fa fa-paper-plane"></i> Tekrar Gönder'; }
        else { m.style.color='#dc3545'; m.textContent = d.message; btn.innerHTML='<i class="fa fa-paper-plane"></i> Kod Gönder'; }
        btn.disabled = false;
    })
    .catch(function(){ m.style.color='#dc3545'; m.textContent='Bir hata oluştu.'; btn.innerHTML='<i class="fa fa-paper-plane"></i> Kod Gönder'; btn.disabled=false; });
}
document.getElementById('verification_code')?.addEventListener('input', function(){
    this.value = this.value.replace(/[^0-9]/g,'').substring(0,6);
});
</script>
@endpush
@endsection