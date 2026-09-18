@extends('layouts.master')

@section('title', $hizmet->adi)
@section('description', $hizmet->kisa ?? Str::limit(strip_tags($hizmet->aciklama), 160))

@section('content')
@php
    use Illuminate\Support\Facades\DB;
    $headerBgStyle = \App\Helpers\HeaderBackgroundHelper::getHeaderBackgroundStyle('hizmet');
@endphp
<div class="top-header overlay" style="{{ $headerBgStyle }}">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading">{{ $hizmet->adi }}</h1>
                    @if($hizmet->kisa)
                    <p class="subheading">{{ $hizmet->kisa }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<section class="modern-section">
    <div class="container">
        <div id="wrapper" class="mt-5 mb-5">
            <div class="row">
                <div class="col-md-8">
                    <div class="modern-card service-detail">
                        @if($hizmet->resim)
                        <div class="service-image mb-4">
                            <img src="{{ asset('tema/uploads/hizmetler/'.$hizmet->resim) }}" 
                                 alt="{{ $hizmet->adi }}" 
                                 class="img-fluid" style="border-radius: 15px; width: 100%;">
                        </div>
                        @endif

                        <div class="service-content" style="color: rgba(255, 255, 255, 0.9); line-height: 1.8;">
                            {!! $hizmet->aciklama !!}
                        </div>

                        @if($hizmet->ozellikler)
                        <div class="service-features mt-5">
                            <h3 class="mb-4">{{ __('messages.features') }}</h3>
                            <ul class="feature-list">
                                @php
                                    $ozellikler = is_array($hizmet->ozellikler) 
                                        ? $hizmet->ozellikler 
                                        : explode(',', $hizmet->ozellikler);
                                @endphp
                                @foreach($ozellikler as $ozellik)
                                <li>
                                    <i class="mdi mdi-check"></i> {{ trim($ozellik) }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Fiyat Kartı -->
                    @if($hizmet->fiyat)
                    <div class="price-card border-left-3 main-content mb-4">
                        <div class="title-area mb-3">
                            <h5 class="title"><i class="mdi mdi-cash"></i> Fiyat Bilgisi</h5>
                        </div>
                        <div class="price-info text-center">
                            <div class="price-amount">
                                <span class="price">{{ number_format($hizmet->fiyat, 2, ',', '.') }} TL</span>
                                @if($hizmet->periyot)
                                <span class="period">/{{ $hizmet->periyot }}</span>
                                @endif
                            </div>
                            @auth('uye')
                            <a href="{{ route('hizmet.satinal', $hizmet->id) }}" class="btn btn-success btn-block mt-3">
                                <i class="mdi mdi-cart"></i> {{ __('messages.buy_now') }}
                            </a>
                            @else
                            <a href="{{ localized_route('giris', ['redirect' => localized_route('hizmet.detay', $hizmet->seo)]) }}" class="btn btn-primary btn-block mt-3">
                                <i class="mdi mdi-login"></i> {{ __('messages.please_login') }}
                            </a>
                            @endauth
                        </div>
                    </div>
                    @endif

                    <!-- İletişim Kartı -->
                    <div class="contact-card border-left-3 main-content mb-4">
                        <div class="title-area mb-3">
                            <h5 class="title"><i class="mdi mdi-email"></i> {{ __('messages.contact_title') }}</h5>
                        </div>
                        <div class="contact-info">
                            <p class="mb-2">
                                <i class="mdi mdi-email"></i>
                                <a href="mailto:{{ $ayarlar->firma_email ?? 'info@example.com' }}">
                                    {{ $ayarlar->firma_email ?? 'info@example.com' }}
                                </a>
                            </p>
                            <p class="mb-2">
                                <i class="mdi mdi-phone"></i>
                                <a href="tel:{{ $ayarlar->firma_telefon ?? '' }}">
                                    {{ $ayarlar->firma_telefon ?? '0850 XXX XX XX' }}
                                </a>
                            </p>
                            <a href="{{ route('iletisim') }}" class="btn btn-outline-primary btn-block mt-3">
                                <i class="mdi mdi-file-document"></i> {{ __('messages.get_quote') }}
                            </a>
                        </div>
                    </div>

                    <!-- Diğer Hizmetler -->
                    @if($diger_hizmetler && count($diger_hizmetler) > 0)
                    <div class="other-services border-left-3 main-content">
                        <div class="title-area mb-3">
                            <h5 class="title"><i class="mdi mdi-format-list-bulleted"></i> {{ __('messages.other_services') }}</h5>
                        </div>
                        <ul class="list-unstyled">
                            @foreach($diger_hizmetler as $diger)
                            <li class="mb-2">
                                <a href="{{ route('hizmet.detay', $diger->seo) }}">
                                    <i class="mdi mdi-arrow-right"></i> {{ $diger->adi }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('tema/css/hizmet.css') }}">
@endpush
@endsection



