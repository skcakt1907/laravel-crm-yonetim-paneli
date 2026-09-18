@extends('layouts.master')

@section('title', 'Doğrulama Kodu')

@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/auth-page.css') }}">
@endpush

@section('content')
<div class="top-header overlay" style="background-image: url({{ asset('tema/uploads/arkaplan/uyelik/login-bg.jpg') }}); background-size: cover; background-position: center;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper text-center">
                    <h1 class="heading">{{ __('messages.verification_code_label') }}</h1>
                    <h3 class="subheading">{{ __('messages.enter_verification_code') }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="auth-page">
    <div class="container auth-container">
        <div class="auth-hero-header">
            <h1 class="heading">{{ __('messages.verification_code_label') }}</h1>
            <p class="subheading">{{ $verificationType == 'email' ? 'E-posta adresinize' : 'Telefon numaranıza' }} gönderilen 6 haneli kodu girin</p>
        </div>
        <div class="auth-card">
            <div class="auth-left">
                <div class="eyebrow">{{ __('messages.verification') }}</div>
                <h1>{{ $ayar->firma_adi ?? config('app.name') }}</h1>
                <p class="lead">
                    {{ $verificationType == 'email' ? 'E-posta adresinize' : 'Telefon numaranıza' }} gönderilen doğrulama kodunu girin.
                </p>
                <ul class="bullet-list">
                    <li>6 haneli kod</li>
                    <li>{{ __('messages.valid_for_10_minutes') }}</li>
                    <li>{{ __('messages.do_not_share_code') }}</li>
                </ul>
                <div class="muted">
                    Kod gelmedi mi?
                    <a href="{{ route('sifre.sifirlama.tekrar') }}">{{ __('messages.send_again') }}</a>
                </div>
            </div>
            <div class="auth-right">
                <div class="badge">{{ __('messages.verification') }}</div>
                <h2>Kodu Girin</h2>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('sifre.sifirlama.kod.dogrula.post') }}" class="form-grid" novalidate>
                    @csrf

                    <div class="form-group">
                        <label class="form-label">
                            <span class="icon"><i class="mdi mdi-key"></i></span>
                            {{ __('messages.verification_code_label') }}
                        </label>
                        <input type="text" name="kod" class="form-input @error('kod') is-invalid @enderror" 
                               value="{{ old('kod') }}" required autofocus maxlength="6" 
                               placeholder="123456" style="text-align: center; font-size: 24px; letter-spacing: 5px;">
                        @error('kod')
                            <div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">{{ __('messages.enter_code_help') }}</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="icon"><i class="mdi mdi-lock"></i></span>
                            {{ __('messages.new_password_label') }}
                        </label>
                        <input type="password" name="new_password" class="form-input @error('new_password') is-invalid @enderror" 
                               required placeholder="{{ __('messages.enter_new_password') }}">
                        @error('new_password')
                            <div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="icon"><i class="mdi mdi-lock-check"></i></span>
                            {{ __('messages.new_password_confirm_label') }}
                        </label>
                        <input type="password" name="new_password_confirmation" class="form-input" 
                               required placeholder="{{ __('messages.repeat_new_password') }}">
                    </div>

                    <button type="submit" class="primary-btn" style="width:100%; justify-content:center;">
                        <i class="mdi mdi-check-circle"></i> {{ __('messages.reset_password_button') }}
                    </button>

                    <div class="muted" style="margin-top:12px;">
                        <a class="muted-link" href="{{ route('sifre.sifirlama.tekrar') }}">{{ __('messages.resend_code_full') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
// Otomatik odak ve kod formatı
document.querySelector('input[name="kod"]').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '').substring(0, 6);
});
</script>
@endpush
@endsection