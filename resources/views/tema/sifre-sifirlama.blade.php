@extends('layouts.master')

@section('title', 'Şifre Sıfırlama')

@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/auth-page.css') }}">
@endpush

@section('content')
<div class="top-header overlay" style="background-image: url({{ asset('tema/uploads/arkaplan/uyelik/login-bg.jpg') }}); background-size: cover; background-position: center;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper text-center">
                    <h1 class="heading">{{ __('messages.password_reset_left_title') }}</h1>
                    <h3 class="subheading">{{ __('messages.password_reset_subtitle') }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="auth-page">
    <div class="container auth-container">
        <div class="auth-hero-header">
            <h1 class="heading">{{ __('messages.password_reset_left_title') }}</h1>
            <p class="subheading">{{ __('messages.password_reset_hero_text') }}</p>
        </div>
        <div class="auth-card">
            <div class="auth-left">
                <div class="eyebrow">{{ __('messages.password_reset_left_title') }}</div>
                <h1>{{ $ayar->firma_adi ?? config('app.name') }}</h1>
                <p class="lead">
                    {{ __('messages.password_reset_left_lead') }}
                </p>
                <ul class="bullet-list">
                    <li>{{ __('messages.password_reset_step_email') }}</li>
                    <li>{{ __('messages.password_reset_step_sms') }}</li>
                    <li>{{ __('messages.set_new_password') }}</li>
                </ul>
                <div class="muted">
                    {{ __('messages.remember_password') }}
                    <a href="{{ route('giris') }}">{{ __('messages.login_here') }}</a>
                </div>
            </div>
            <div class="auth-right">
                <div class="badge">{{ __('messages.password_reset_left_title') }}</div>
                <h2>E-posta veya Telefon</h2>

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ route('sifre.sifirlama.kod.gonder') }}" class="form-grid" novalidate>
                    @csrf

                    <div class="form-group">
                        <label class="form-label">
                            <span class="icon"><i class="mdi mdi-email"></i></span>
                            {{ __('messages.email_or_phone') }}
                        </label>
                        <input type="text" name="email_or_phone" class="form-input @error('email_or_phone') is-invalid @enderror" 
                               value="{{ old('email_or_phone') }}" required autofocus 
                               placeholder="ornek@email.com veya 5551234567">
                        @error('email_or_phone')
                            <div class="error-text"><i class="mdi mdi-alert-circle"></i>{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">{{ __('messages.enter_email_or_phone') }}</small>
                    </div>

                    <button type="submit" class="primary-btn" style="width:100%; justify-content:center;">
                        <i class="mdi mdi-send"></i> {{ __('messages.send_verification_code') }}
                    </button>

                    <div class="muted" style="margin-top:12px;">
                        {{ __('messages.remember_password') }} <a class="muted-link" href="{{ route('giris') }}">{{ __('messages.login_here') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
