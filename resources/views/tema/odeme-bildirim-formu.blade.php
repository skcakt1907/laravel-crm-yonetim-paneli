@extends('layouts.master')

@section('title', 'Ödeme Bildirim Formu')

@section('content')
@php
    $headerBgStyle = \App\Helpers\HeaderBackgroundHelper::getHeaderBackgroundStyle('sayfa');
@endphp
<div class="top-header overlay" style="{{ $headerBgStyle }} padding: 180px 0 80px 0; min-height: 360px; display: flex; align-items: flex-end;">
    <div class="container">
        <div class="wrapper" style="padding-bottom: 40px;">
            <h1 class="heading" style="margin-bottom: 15px; font-size: 38px;">{{ __('messages.payment_notice_form') }}</h1>
            <p class="subheading" style="line-height: 1.8; font-size: 16px;">{{ __('messages.payment_notice_form_desc') }}</p>
        </div>
    </div>
</div>

<section style="background:#f4f4ef; padding:60px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">

                @if(session('success'))
                <div style="background:#e6f7ef; border:1px solid #1d9e75; color:#0f6e56; padding:16px 20px; border-radius:12px; margin-bottom:24px; font-size:14.5px;">
                    <i class="mdi mdi-check-circle"></i> {{ session('success') }}
                </div>
                @endif
                @if(session('error'))
                <div style="background:#fdecec; border:1px solid #e24b4a; color:#a32d2d; padding:16px 20px; border-radius:12px; margin-bottom:24px; font-size:14.5px;">
                    <i class="mdi mdi-alert-circle"></i> {{ session('error') }}
                </div>
                @endif
                @if($errors->any())
                <div style="background:#fdecec; border:1px solid #e24b4a; color:#a32d2d; padding:16px 20px; border-radius:12px; margin-bottom:24px; font-size:14.5px;">
                    <ul style="margin:0; padding-left:18px;">
                        @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form action="{{ route('odeme.bildirim.formu.post') }}" method="post" autocomplete="off"
                      style="background:#ffffff; border:1px solid #e8e8e0; border-radius:18px; padding:38px; box-shadow:0 4px 24px rgba(15,23,42,.07);">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label style="display:block; color:#5f5e2a; font-size:13px; font-weight:700; margin-bottom:8px;">
                                <i class="mdi mdi-account"></i> {{ __('messages.your_name') }} <span style="color:#e24b4a;">*</span>
                            </label>
                            <input type="text" name="isim" value="{{ old('isim') }}" required placeholder="{{ __('messages.your_name') }}"
                                   style="width:100%; background:#f9f9f4; border:1px solid #e0e0d6; border-radius:10px; padding:13px 16px; color:#1a1a1a; font-size:14.5px; outline:none; transition:border-color .2s;"
                                   onfocus="this.style.borderColor='#b8b62e'" onblur="this.style.borderColor='#e0e0d6'">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label style="display:block; color:#5f5e2a; font-size:13px; font-weight:700; margin-bottom:8px;">
                                <i class="mdi mdi-cash"></i> {{ __('messages.deposited_amount') }} <span style="color:#e24b4a;">*</span>
                            </label>
                            <input type="text" name="tutar" value="{{ old('tutar') }}" required placeholder="{{ __('messages.eg_1500') }}"
                                   style="width:100%; background:#f9f9f4; border:1px solid #e0e0d6; border-radius:10px; padding:13px 16px; color:#1a1a1a; font-size:14.5px; outline:none; transition:border-color .2s;"
                                   onfocus="this.style.borderColor='#b8b62e'" onblur="this.style.borderColor='#e0e0d6'">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label style="display:block; color:#5f5e2a; font-size:13px; font-weight:700; margin-bottom:8px;">
                                <i class="mdi mdi-calendar"></i> {{ __('messages.deposit_date') }} <span style="color:#e24b4a;">*</span>
                            </label>
                            <input type="text" name="tarih" value="{{ old('tarih') }}" required placeholder="GG/AA/YYYY"
                                   style="width:100%; background:#f9f9f4; border:1px solid #e0e0d6; border-radius:10px; padding:13px 16px; color:#1a1a1a; font-size:14.5px; outline:none; transition:border-color .2s;"
                                   onfocus="this.style.borderColor='#b8b62e'" onblur="this.style.borderColor='#e0e0d6'">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label style="display:block; color:#5f5e2a; font-size:13px; font-weight:700; margin-bottom:8px;">
                                <i class="mdi mdi-bank"></i> {{ __('messages.deposit_bank') }} <span style="color:#e24b4a;">*</span>
                            </label>
                            <input type="text" name="banka" value="{{ old('banka') }}" required placeholder="{{ __('messages.eg_bank') }}"
                                   style="width:100%; background:#f9f9f4; border:1px solid #e0e0d6; border-radius:10px; padding:13px 16px; color:#1a1a1a; font-size:14.5px; outline:none; transition:border-color .2s;"
                                   onfocus="this.style.borderColor='#b8b62e'" onblur="this.style.borderColor='#e0e0d6'">
                        </div>
                        <div class="col-md-12 mb-4">
                            <label style="display:block; color:#5f5e2a; font-size:13px; font-weight:700; margin-bottom:8px;">
                                <i class="mdi mdi-message-text"></i> Notunuz
                            </label>
                            <textarea name="notunuz" rows="4" placeholder="{{ __('messages.optional_note_placeholder') }}"
                                      style="width:100%; background:#f9f9f4; border:1px solid #e0e0d6; border-radius:10px; padding:13px 16px; color:#1a1a1a; font-size:14.5px; outline:none; resize:vertical; transition:border-color .2s;"
                                      onfocus="this.style.borderColor='#b8b62e'" onblur="this.style.borderColor='#e0e0d6'">{{ old('notunuz') }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <button type="submit"
                                    style="background:#b8b62e; color:#1a1a0e; font-weight:800; font-size:15px; padding:14px 38px; border:none; border-radius:12px; cursor:pointer; box-shadow:0 6px 18px rgba(184,182,46,.30); transition:all .2s;"
                                    onmouseover="this.style.background='#a3a128'; this.style.transform='translateY(-2px)'" onmouseout="this.style.background='#b8b62e'; this.style.transform='translateY(0)'">
                                <i class="mdi mdi-send"></i> {{ __('messages.submit') }}
                            </button>
                            <button type="reset"
                                    style="background:#f0f0e8; color:#666; font-weight:600; font-size:15px; padding:14px 28px; border:1px solid #e0e0d6; border-radius:12px; cursor:pointer; margin-left:10px;">
                                <i class="mdi mdi-refresh"></i> Temizle
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>
</section>
@endsection