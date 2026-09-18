@extends('layouts.master')

@section('title', __('messages.contact'))

@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/iletisim.css') }}">
@endpush

@section('content')
@php
    use Illuminate\Support\Facades\DB;
@endphp
<div style="background:#fff; padding: 110px 0 26px; border-bottom:1px solid #e8e8e2;">
    <div class="container">
        <div style="font-size:13px; color:#999; margin-bottom:10px;">
            <a href="{{ localized_route('anasayfa') }}" style="color:#999; text-decoration:none;">{{ __('messages.home') }}</a>
            <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
            <span style="color:#1a1a1a; font-weight:600;">{{ __('messages.contact') }}</span>
        </div>
        <h1 style="color:#1a1a1a; font-size:28px; font-weight:700; margin:0;">{{ __('messages.contact') }}</h1>
        <p style="color:#999; font-size:14px; margin:6px 0 0;">{{ __('messages.contact_us') }}</p>
    </div>
</div>

<!-- LOCATION & CONTACT INFO -->
<section class="contact-info-section" style="padding: 60px 0;">
    <div class="container">
        <div class="row">
            <!-- Address Card -->
            <div class="col-sm-12 col-md-6 mb-4">
                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="mdi mdi-map-marker"></i>
                    </div>
                    <div class="contact-info-content">
                        <div class="contact-info-badge">{{ __('messages.address') }}</div>
                        <h3 class="contact-info-title">{{ $ayarlar->firma_adi ?? config('app.name') }}</h3>
                        <p class="contact-info-text">{{ $ayarlar->firma_adres ?? 'Adres Bilgisi' }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Phone & Email Card -->
            <div class="col-sm-12 col-md-6 mb-4">
                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="mdi mdi-phone" style="font-size: 32px;"></i>
                    </div>
                    <div class="contact-info-content">
                        <div class="contact-info-badge">{{ __('messages.contact') }}</div>
                        <div class="contact-info-links">
                            <a href="tel:{{ $ayarlar->firma_telefon ?? '' }}" class="contact-link-item">
                                <i class="mdi mdi-phone"></i>
                                <span>{{ $ayarlar->firma_telefon ?? '+90 555 555 55 55' }}</span>
                            </a>
                            <a href="mailto:{{ $ayarlar->firma_email ?? '' }}" class="contact-link-item">
                                <i class="mdi mdi-email"></i>
                                <span>{{ $ayarlar->firma_email ?? 'info@example.com' }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- HELP / SUPPORT CARDS -->
<section class="help-section" style="padding: 40px 0 60px 0;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-6 col-lg-4 mb-4">
                <a href="{{ localized_route('hesabim') }}" class="help-card-modern">
                    <div class="help-card-icon">
                        <img class="svg ico" src="{{ asset('tema/fonts/svg/livechat.svg') }}" height="65" alt="">
                    </div>
                    <div class="help-card-content">
                        <h3 class="help-card-title">{{ __('messages.live_support') }}</h3>
                        <p class="help-card-description">{{ __('messages.live_support_desc') }}</p>
                    </div>
                    <div class="help-card-arrow">
                        <i class="mdi mdi-arrow-right"></i>
                    </div>
                </a>
            </div>
            
            <div class="col-sm-12 col-md-6 col-lg-4 mb-4">
                <a href="mailto:{{ $ayarlar->firma_email ?? '' }}" class="help-card-modern">
                    <div class="help-card-icon">
                        <img class="svg ico" src="{{ asset('tema/fonts/svg/emailopen.svg') }}" height="65" alt="">
                    </div>
                    <div class="help-card-content">
                        <h3 class="help-card-title">{{ __('messages.email_support') }}</h3>
                        <p class="help-card-description">{{ $ayarlar->firma_email ?? 'info@example.com' }}</p>
                    </div>
                    <div class="help-card-arrow">
                        <i class="mdi mdi-arrow-right"></i>
                    </div>
                </a>
            </div>
            
            <div class="col-sm-12 col-md-6 col-lg-4 mb-4">
                <a href="tel:{{ $ayarlar->firma_telefon ?? '' }}" class="help-card-modern">
                    <div class="help-card-icon">
                        <img class="svg ico" src="{{ asset('tema/fonts/svg/phone.svg') }}" height="65" alt="">
                    </div>
                    <div class="help-card-content">
                        <h3 class="help-card-title">{{ __('messages.phone') }}</h3>
                        <p class="help-card-description">{{ $ayarlar->firma_telefon ?? '+90 555 555 55 55' }}</p>
                    </div>
                    <div class="help-card-arrow">
                        <i class="mdi mdi-arrow-right"></i>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- MAP -->
@if(isset($ayarlar->google_maps) && !empty($ayarlar->google_maps))
<section class="services maping sec-normal p-0">
    <div class="service-wrap">
        {!! $ayarlar->google_maps !!}
    </div>
</section>
@endif

<!-- CONTACT FORM -->
<section id="ticket" class="pb-80" style="padding-top: 60px;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="contact-form-wrapper">
                    <div class="form-header text-center mb-5">
                        <h2 class="form-title" style="color: #0f172a; font-size: 36px; font-weight: 700; margin-bottom: 15px;">
                            <i class="mdi mdi-email" style="color: #b8b62e;"></i>
                            {{ __('messages.contact_us') }}
                        </h2>
                        <p style="color: #9ca3af; font-size: 16px; line-height: 1.8;">
                            {{ __('messages.contact_info_text') }}
                        </p>
                    </div>
                    
                    @include('tema.partials.alert-messages')

                    <form action="{{ localized_route('iletisim.post') }}" method="post" autocomplete="off" class="contact-form-modern">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="mdi mdi-account"></i> {{ __('messages.your_name') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" name="isim" value="{{ old('isim') }}" required 
                                           class="form-input-modern @error('isim') is-invalid @enderror"
                                           placeholder="{{ __('messages.your_name') }}">
                                    @error('isim')
                                    <div class="error-message">
                                        <i class="mdi mdi-alert-circle"></i> {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="mdi mdi-email"></i> {{ __('messages.your_email') }} <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" name="email" value="{{ old('email') }}" required 
                                           class="form-input-modern @error('email') is-invalid @enderror"
                                           placeholder="{{ __('messages.your_email') }}">
                                    @error('email')
                                    <div class="error-message">
                                        <i class="mdi mdi-alert-circle"></i> {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="mdi mdi-phone"></i> {{ __('messages.your_phone') }}
                                    </label>
                                    <input type="text" name="telefon" value="{{ old('telefon') }}" 
                                           class="form-input-modern @error('telefon') is-invalid @enderror"
                                           placeholder="{{ __('messages.your_phone') }}">
                                    @error('telefon')
                                    <div class="error-message">
                                        <i class="mdi mdi-alert-circle"></i> {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="mdi mdi-tag"></i> {{ __('messages.subject') }}
                                    </label>
                                    <input type="text" name="konu" value="{{ old('konu') }}" 
                                           class="form-input-modern @error('konu') is-invalid @enderror"
                                           placeholder="{{ __('messages.subject') }}">
                                    @error('konu')
                                    <div class="error-message">
                                        <i class="mdi mdi-alert-circle"></i> {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-12 mb-4">
                                <div class="form-group-modern">
                                    <label class="form-label-modern">
                                        <i class="mdi mdi-message-text"></i> {{ __('messages.your_message') }} <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="mesaj" rows="6" required 
                                              class="form-input-modern form-textarea-modern @error('mesaj') is-invalid @enderror"
                                              placeholder="{{ __('messages.your_message') }}">{{ old('mesaj') }}</textarea>
                                    @error('mesaj')
                                    <div class="error-message">
                                        <i class="mdi mdi-alert-circle"></i> {{ $message }}
                                    </div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="col-md-12">
                                <div class="form-actions">
                                    <button type="submit" class="btn-submit-modern">
                                        <i class="mdi mdi-send"></i>
                                        {{ __('messages.send') }}
                                    </button>
                                    <button type="reset" class="btn-reset-modern">
                                        <i class="mdi mdi-refresh"></i>
                                        {{ __('messages.reset') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
@if(session('success'))
<script id="successMessage" type="application/json">{{ session('success') }}</script>
@endif
@if(session('error'))
<script id="errorMessage" type="application/json">{{ session('error') }}</script>
@endif
<script src="{{ asset('tema/js/iletisim.js') }}"></script>
@endpush

@endsection