@extends('layouts.master')

@section('title', 'Referanslar')

@section('content')
@php
    use Illuminate\Support\Facades\DB;
    $headerBgStyle = \App\Helpers\HeaderBackgroundHelper::getHeaderBackgroundStyle('referanslar');
@endphp
<div class="top-header overlay" style="{{ $headerBgStyle }} padding: 180px 0 80px 0; min-height: 500px; display: flex; align-items: flex-end;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper" style="padding-bottom: 60px;">
                    <h1 class="heading" style="margin-bottom: 25px; font-size: 42px;">{{ __('messages.our_references') }}</h1>
                    <h3 class="subheading" style="line-height: 2; font-size: 18px;">{{ __('messages.our_successful_projects') }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="modern-section">
    <div class="container">
        <div class="row">
            @forelse($referanslar as $referans)
            <div class="col-md-3 col-sm-6 mb-4">
                <div class="modern-card text-center">
                    @if($referans->resim)
                    @php
                        $resim_yolu = str_starts_with($referans->resim, 'tema/') ? $referans->resim : 'tema/uploads/referanslar/'.$referans->resim;
                    @endphp
                    <img src="{{ asset($resim_yolu) }}" alt="{{ $referans->adi }}" class="img-fluid" style="border-radius: 12px; margin-bottom: 20px; max-height: 200px; object-fit: cover; width: 100%;">
                    @endif
                    <h4 class="mt-3" style="color: #fff; font-size: 18px; font-weight: 700; margin-bottom: 10px;">{{ $referans->adi }}</h4>
                    @if($referans->kisa)
                    <p style="color: rgba(255, 255, 255, 0.7); font-size: 14px; line-height: 1.6;">{{ Str::limit($referans->kisa, 100) }}</p>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="modern-alert modern-alert-info">
                    <i class="mdi mdi-information"></i>
                    <strong>{{ __('messages.info_label') }}</strong> {{ __('messages.no_references') }}
                </div>
            </div>
            @endforelse
        </div>
    </div>
</section>
@endsection
