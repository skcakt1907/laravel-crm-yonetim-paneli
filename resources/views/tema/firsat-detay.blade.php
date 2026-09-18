@extends('layouts.master')

@section('title', $kampanya->baslik ?? __('messages.opportunity_detail'))

@section('head')
<link rel="stylesheet" href="{{ asset('tema/css/paketler.css') }}">
@endsection

@section('content')
@php
    $currency = request('currency') ?? session('currency', 'TRY');
    $resim_yolu = null;
    if (!empty($kampanya->resim)) {
        $resim_path = $kampanya->resim;
        if (strpos($resim_path, 'tema/') === 0 || strpos($resim_path, '/tema/') === 0) {
            $resim_yolu = asset($resim_path);
        } else {
            $resim_yolu = asset('tema/uploads/kampanyalar/' . $resim_path);
        }
    }
@endphp

<!-- ***** BAŞLIK + BREADCRUMB (açık tema, liste ile uyumlu) ***** -->
<div style="background:#fff; padding: 110px 0 26px; border-bottom:1px solid #e8e8e2;">
  <div class="container">
    <div style="font-size:13px; color:#999; margin-bottom:10px;">
      <a href="{{ route('anasayfa') }}" style="color:#999; text-decoration:none;">{{ __('messages.home') }}</a>
      <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
      <a href="{{ route('firsatlar') }}" style="color:#999; text-decoration:none;">{{ __('messages.opportunities_title') }}</a>
      <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
      <span style="color:#1a1a1a; font-weight:600;">{{ $kampanya->baslik ?? __('messages.opportunities_title') }}</span>
    </div>
    <h1 style="color:#1a1a1a; font-size:28px; font-weight:700; margin:0;">{{ $kampanya->baslik ?? __('messages.opportunities_title') }}</h1>
    @if(($kampanya->indirim ?? 0) > 0)
    <span style="display:inline-block; margin-top:10px; background:#b8b62e; color:#1a1a0e; padding:6px 16px; border-radius:8px; font-weight:700; font-size:14px;">
      %{{ number_format($kampanya->indirim, 0) }} {{ __('messages.discount') }}
    </span>
    @endif
  </div>
</div>

<!-- ***** İÇERİK ***** -->
<div style="background:#f0f0ec; padding: 40px 0 70px;">
  <div class="container">
    <div style="display:grid; grid-template-columns: minmax(0, 2fr) minmax(280px, 1fr); gap:24px; max-width:1100px; margin:0 auto; align-items:start;">

      <!-- Sol: Detay kartı -->
      <div style="background:#fff; border:1px solid #ececec; border-radius:16px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        @if($resim_yolu)
        <div style="position:relative; height:340px; overflow:hidden; background:#f0f0ec;">
          <img src="{{ $resim_yolu }}" alt="{{ $kampanya->baslik }}" style="width:100%; height:100%; object-fit:cover; display:block;" onerror="this.style.display='none'; this.parentElement.style.background='linear-gradient(135deg, rgba(184,182,46,.12) 0%, rgba(184,182,46,.04) 100%)';">
        </div>
        @else
        <div style="height:200px; background:linear-gradient(135deg, rgba(184,182,46,.12) 0%, rgba(184,182,46,.04) 100%); display:flex; align-items:center; justify-content:center;">
          <i class="mdi mdi-tag-multiple" style="font-size:64px; color:#b8b62e; opacity:.85;"></i>
        </div>
        @endif

        <div style="padding:34px;">
          <h2 style="color:#1a1a1a; font-size:26px; font-weight:700; margin:0 0 18px;">{{ $kampanya->baslik }}</h2>

          @if($kampanya->aciklama)
          <div style="color:#555; font-size:16px; line-height:1.8; margin-bottom:26px;">
            {!! nl2br(e($kampanya->aciklama)) !!}
          </div>
          @endif

          @if(($kampanya->baslangic_tarihi ?? null) || ($kampanya->bitis_tarihi ?? null))
          <div style="background:rgba(184,182,46,.08); border-left:4px solid #b8b62e; padding:16px 20px; border-radius:8px; margin-bottom:20px;">
            <h4 style="color:#9a981f; font-size:16px; font-weight:700; margin:0 0 8px;">
              <i class="mdi mdi-calendar-clock"></i> {{ __('messages.campaign_period') }}
            </h4>
            <p style="color:#555; margin:0; font-size:14px; line-height:1.7;">
              @if($kampanya->baslangic_tarihi)
                <strong style="color:#1a1a1a;">{{ __('messages.campaign_start') }}:</strong> {{ \Carbon\Carbon::parse($kampanya->baslangic_tarihi)->format('d.m.Y') }}
              @endif
              @if(($kampanya->baslangic_tarihi ?? null) && ($kampanya->bitis_tarihi ?? null))<br>@endif
              @if($kampanya->bitis_tarihi)
                <strong style="color:#1a1a1a;">{{ __('messages.campaign_end') }}:</strong> {{ \Carbon\Carbon::parse($kampanya->bitis_tarihi)->format('d.m.Y') }}
              @endif
            </p>
          </div>
          @endif

          @if($kampanya->link)
          <div style="margin-top:26px;">
            <a href="{{ $kampanya->link }}" target="_blank" style="display:inline-flex; align-items:center; gap:8px; background:#b8b62e; color:#1a1a0e; padding:14px 28px; font-weight:700; font-size:15px; border-radius:10px; text-decoration:none; transition:background .2s ease;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';">
              <i class="mdi mdi-open-in-new"></i> {{ __('messages.go_to_campaign_link') }}
            </a>
          </div>
          @endif
        </div>
      </div>

      <!-- Sağ: Özet kartı -->
      <div style="background:#fff; border:1px solid #ececec; border-radius:16px; padding:28px; box-shadow:0 2px 8px rgba(0,0,0,0.06); position:sticky; top:90px;">
        <h3 style="color:#1a1a1a; font-size:20px; font-weight:700; margin:0 0 20px;">{{ __('messages.campaign_summary') }}</h3>

        @if(($kampanya->indirim ?? 0) > 0)
        <div style="background:linear-gradient(135deg, #b8b62e 0%, #9a981f 100%); color:#1a1a0e; padding:22px; border-radius:12px; text-align:center; margin-bottom:20px;">
          <div style="font-size:13px; opacity:.85; margin-bottom:4px;">{{ __('messages.discount_rate') }}</div>
          <div style="font-size:46px; font-weight:800; line-height:1;">%{{ number_format($kampanya->indirim, 0) }}</div>
        </div>
        @endif

        @if($kampanya->link)
        <a href="{{ $kampanya->link }}" target="_blank" style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; background:#b8b62e; color:#1a1a0e; padding:14px; font-weight:700; font-size:15px; border-radius:10px; text-decoration:none; margin-bottom:12px; transition:background .2s ease;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';">
          <i class="mdi mdi-cart"></i> {{ __('messages.join_campaign') }}
        </a>
        @endif

        <a href="{{ route('firsatlar') }}" style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; background:#fff; color:#555; padding:13px; font-weight:600; font-size:14px; border-radius:10px; text-decoration:none; border:1.5px solid #e0e0d8; transition:all .2s ease;" onmouseover="this.style.borderColor='#b8b62e'; this.style.color='#1a1a1a';" onmouseout="this.style.borderColor='#e0e0d8'; this.style.color='#555';">
          <i class="mdi mdi-arrow-left"></i> {{ __('messages.view_all_opportunities') }}
        </a>
      </div>

    </div>
  </div>
</div>
@endsection