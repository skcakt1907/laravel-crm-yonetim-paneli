@extends('layouts.master')

@section('title', __('messages.login'))

@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/auth-page.css') }}">
<!-- 419 hatası önleme: sayfa tarayıcı cache'inden gelmesin, her seferinde fresh CSRF token -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
@endpush

@section('content')
@php $redirect = request('redirect'); @endphp

{{-- ═══ KÖŞE UYARI KUTULARI (admin paneli tarzı, sağ üstte belirir) ═══ --}}
<style>
.cl-alert-stack{position:fixed;top:20px;right:20px;z-index:99999;max-width:380px;width:calc(100% - 40px);display:flex;flex-direction:column;gap:10px;pointer-events:none}
.cl-alert{pointer-events:auto;display:flex;align-items:flex-start;gap:12px;padding:13px 15px;border-radius:12px;background:#fff;border:1px solid #e5e7eb;box-shadow:0 24px 48px -12px rgba(0,0,0,.18);animation:clSlideIn .25s ease-out;font-family:'Poppins',system-ui,sans-serif}
.cl-alert-icon{width:32px;height:32px;border-radius:8px;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0;font-size:18px;font-weight:700}
.cl-alert-body{flex:1;font-size:13.5px;line-height:1.5;color:#1a1d24;font-weight:500}
.cl-alert-close{background:transparent;border:none;color:#9ca3af;cursor:pointer;padding:4px;border-radius:6px;font-size:16px;line-height:1}
.cl-alert-close:hover{background:#f3f4f6;color:#1a1d24}
.cl-alert-danger{border-color:rgba(239,68,68,.3)}
.cl-alert-danger .cl-alert-icon{background:rgba(239,68,68,.12);color:#ef4444}
.cl-alert-success{border-color:rgba(16,185,129,.3)}
.cl-alert-success .cl-alert-icon{background:rgba(16,185,129,.12);color:#10b981}
@keyframes clSlideIn{from{transform:translateX(120%);opacity:0}to{transform:translateX(0);opacity:1}}
@keyframes clSlideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(120%);opacity:0}}
.cl-alert.cl-dismissing{animation:clSlideOut .25s ease-in forwards}
</style>

<div class="cl-alert-stack" id="clAlertStack">
    {{-- URL query ile gelen mesaj (flash session redirect zincirinde kaybolsa bile calisir) --}}
    @if(request('hata'))
    <div class="cl-alert cl-alert-danger">
        <div class="cl-alert-icon">!</div>
        <div class="cl-alert-body">{{ request('hata') }}</div>
        <button type="button" class="cl-alert-close" onclick="clDismissAlert(this)">&times;</button>
    </div>
    @endif

    @if(request('hosgeldin'))
    <div class="cl-alert cl-alert-success">
        <div class="cl-alert-icon">&checkmark;</div>
        <div class="cl-alert-body">{{ __('messages.welcome_excl') }}</div>
        <button type="button" class="cl-alert-close" onclick="clDismissAlert(this)">&times;</button>
    </div>
    @endif

    @if(request('basari'))
    <div class="cl-alert cl-alert-success">
        <div class="cl-alert-icon">&checkmark;</div>
        <div class="cl-alert-body">{{ request('basari') }}</div>
        <button type="button" class="cl-alert-close" onclick="clDismissAlert(this)">&times;</button>
    </div>
    @endif

    @if(session('error'))
    <div class="cl-alert cl-alert-danger">
        <div class="cl-alert-icon">!</div>
        <div class="cl-alert-body">{{ session('error') }}</div>
        <button type="button" class="cl-alert-close" onclick="clDismissAlert(this)">&times;</button>
    </div>
    @endif

    @if(!session('error') && $errors->any())
    <div class="cl-alert cl-alert-danger">
        <div class="cl-alert-icon">!</div>
        <div class="cl-alert-body">{{ $errors->first() }}</div>
        <button type="button" class="cl-alert-close" onclick="clDismissAlert(this)">&times;</button>
    </div>
    @endif

    @if(session('success'))
    <div class="cl-alert cl-alert-success">
        <div class="cl-alert-icon">&checkmark;</div>
        <div class="cl-alert-body">{{ session('success') }}</div>
        <button type="button" class="cl-alert-close" onclick="clDismissAlert(this)">&times;</button>
    </div>
    @endif
</div>

<div class="top-header overlay" style="background-image: url({{ asset('tema/uploads/arkaplan/uyelik/login-bg.jpg') }}); background-size: cover; background-position: center;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper text-center">
                    <h1 class="heading">{{ __('messages.customer_login') }}</h1>
                    <h3 class="subheading">{{ __('messages.welcome_back') ?? 'Tekrar hoş geldiniz' }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="auth-page">
    <div class="container auth-container">
        <div class="auth-card">
            <div class="auth-left">
                <div class="eyebrow">{{ __('messages.customer_login') }}</div>
                <h1>{{ $ayar->firma_adi ?? config('app.name') }}</h1>
                <p class="lead">
                    {{ __('messages.login_desc') }}
                </p>
                <ul class="bullet-list">
                    <li>{{ __('messages.manage_services') }}</li>
                    <li>{{ __('messages.track_invoices') }}</li>
                    <li>{{ __('messages.get_support') }}</li>
                </ul>
                <div class="muted">
                    {{ __('messages.need_help') }}
                    <a href="{{ localized_route('iletisim') }}">{{ __('messages.contact_support') }}</a>
                </div>
            </div>
            <div class="auth-right">
                <div class="badge">{{ __('messages.login') }}</div>
                <h2>{{ __('messages.login_account') }}</h2>


                <form method="POST" action="{{ route('giris.post') }}" class="form-grid" id="loginForm">
                    @csrf
                    @if($redirect)
                        <input type="hidden" name="redirect" value="{{ $redirect }}">
                    @endif

                    <div class="form-group">
                        <label class="form-label">
                            <span class="icon"><i class="mdi mdi-email"></i></span>
                            {{ __('messages.email_or_username') }}
                        </label>
                        <input type="text" name="email" class="form-input @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="{{ __('messages.email_or_username_placeholder') }}">
                        @error('email')
                            <div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            {{ __('messages.password') }}
                        </label>
                        <div style="position:relative;">
                            <input type="password" name="password" id="loginPassword" class="form-input @error('password') is-invalid @enderror" required autocomplete="current-password" placeholder="{{ __('messages.password') }}" style="padding-right:42px;">
                            <button type="button" onclick="toggleLoginPassword()" aria-label="{{ __('messages.toggle_password') }}"
                                    style="position:absolute;top:50%;right:10px;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#9ca3af;padding:4px;display:flex;align-items:center;">
                                <i class="mdi mdi-eye" id="loginPasswordIcon" style="font-size:20px;"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="auth-actions">
                        <label class="checkbox-row">
                            <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span>{{ __('messages.remember_me') }}</span>
                        </label>
                        <a class="muted-link" href="{{ route('sifre.sifirlama') }}">{{ __('messages.forgot_password') }}</a>
                    </div>

                    <button type="submit" class="primary-btn" style="width:100%; justify-content:center;" id="loginBtn">
                        <i class="mdi mdi-login"></i> {{ __('messages.login_now') }}
                    </button>

                    <div class="muted" style="margin-top:12px;">
                        {{ __('messages.no_account') }} <a class="muted-link" href="{{ localized_route('kayit') }}">{{ __('messages.register_now') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
// Köşe uyarı kutusu kapatma
function clDismissAlert(btn){
    var a = btn.closest('.cl-alert');
    if(!a) return;
    a.classList.add('cl-dismissing');
    setTimeout(function(){ a.remove(); }, 250);
}
// Başarı mesajlarını 5 sn sonra otomatik kapat (hata mesajları kullanıcı kapatana kadar kalır)
setTimeout(function(){
    document.querySelectorAll('.cl-alert-success').forEach(function(a){
        a.classList.add('cl-dismissing');
        setTimeout(function(){ a.remove(); }, 250);
    });
}, 5000);

// NOT: Eskiden burada "pageshow -> window.location.reload()" vardi.
// O kod, hatali giris sonrasi back() ile donen sayfayi yeniden yukluyor,
// flash hata mesajini (session('error')) tuketip siliyordu -> uyari hic gorunmuyordu.
// KALDIRILDI. Artik yanlis sifre / kullanici yok uyarilari saglikli gorunur.

function toggleLoginPassword() {
    var inp = document.getElementById('loginPassword');
    var icon = document.getElementById('loginPasswordIcon');
    if (!inp) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        if (icon) icon.className = 'mdi mdi-eye-off';
    } else {
        inp.type = 'password';
        if (icon) icon.className = 'mdi mdi-eye';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('loginForm');
    var submitBtn = document.getElementById('loginBtn');

    if (form) {
        form.addEventListener('submit', function(e) {
            var email = form.querySelector('input[name="email"]').value.trim();
            var password = form.querySelector('input[name="password"]').value;

            if (!email) {
                e.preventDefault();
                alert('E-posta veya kullanıcı adı boş olamaz!');
                return false;
            }
            if (!password) {
                e.preventDefault();
                alert('Şifre boş olamaz!');
                return false;
            }

            // Butonu disable et ve loading goster
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="mdi mdi-loading mdi-spin"></i> Giriş yapılıyor...';
            }
        });
    }
});
</script>
@endpush
@endsection