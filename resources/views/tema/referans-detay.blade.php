@extends('layouts.master')

@section('content')
<!-- ***** HEADER ***** -->
<div class="top-header overlay" style="background-image: url({{ asset('tema/uploads/arkaplan/referanslar/'.($arkaplan->referanslar ?? 'bg.jpg')) }}); padding: 180px 0 80px 0; min-height: 500px; display: flex; align-items: flex-end;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper" style="padding-bottom: 60px;">
                    <h1 class="heading" style="margin-bottom: 25px; font-size: 42px;">{{ $referans->adi ?? 'Referans Detayı' }}</h1>
                    <h3 class="subheading" style="line-height: 2; font-size: 18px;">{{ $referans->kisa ?? '' }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ***** BREADCRUMB ***** -->
<div class="ustbanner">
    <div class="container">
        <a href="{{ url('/') }}" class="golink gocheck">{{ __('messages.home') }}</a> 
        <small class="c-white">&#9679;</small>
        <a href="javascript:void(0)" class="golink gocheck">{{ $referans->adi ?? 'Referans Detayı' }}</a>
    </div>
</div>

<!-- ***** REFERANS DETAY ***** -->
<section class="about sec-normal">
    <div class="container">
        <div class="row">
            @if($referans)
            <div class="col-md-12">
                <div class="sec-main sec-bg1">
                    @if($referans->resim)
                    @php
                        $resim_yolu = str_starts_with($referans->resim, 'tema/') ? $referans->resim : 'tema/uploads/referanslar/'.$referans->resim;
                    @endphp
                    <div class="row mb-4">
                        <div class="col-md-12 text-center">
                            <img src="{{ asset($resim_yolu) }}" 
                                 alt="{{ $referans->adi }}" 
                                 class="img-fluid" 
                                 style="max-width: 100%; height: auto; border-radius: 8px;">
                        </div>
                    </div>
                    @endif
                    
                    <div class="row">
                        <div class="col-md-12">
                            <h2>{{ $referans->adi }}</h2>
                            
                            @if($referans->kisa)
                            <p class="lead">{{ $referans->kisa }}</p>
                            @endif
                            
                            @if($referans->aciklama)
                            <div class="content mt-4">
                                {!! $referans->aciklama !!}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="col-md-12">
                <div class="alert alert-warning">
                    <strong>{{ __('messages.panel_warning_title') }}</strong> {{ __('messages.reference_not_found') }}
                </div>
            </div>
            @endif
        </div>
    </div>
</section>
@endsection






