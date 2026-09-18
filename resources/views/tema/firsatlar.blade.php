@extends('layouts.master')

@section('title', __('messages.opportunities_title'))

@section('head')
<link rel="stylesheet" href="{{ asset('tema/css/paketler.css') }}">
@endsection

@section('content')
@php
    use Illuminate\Support\Facades\DB;
    $currency = session('currency', 'TRY');
    $sembol = match($currency) {
      'USD' => '$',
      'EUR' => '€',
      'AED' => 'د.إ',
      default => '₺'
    };
@endphp

<div style="background:#fff; padding: 110px 0 26px; border-bottom:1px solid #e8e8e2;">
  <div class="container">
    <div style="font-size:13px; color:#999; margin-bottom:10px;">
      <a href="{{ route('anasayfa') }}" style="color:#999; text-decoration:none;">{{ __('messages.home') }}</a>
      <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
      <span style="color:#1a1a1a; font-weight:600;">{{ __('messages.opportunities_title') }}</span>
    </div>
    <h1 style="color:#1a1a1a; font-size:28px; font-weight:700; margin:0;">{{ __('messages.opportunities_title') }}</h1>
    <p style="color:#999; font-size:14px; margin:6px 0 0;">{{ __('messages.opportunities_subtitle') }}</p>
  </div>
</div>

<div style="background:#f0f0ec; padding: 0 0 70px;">
  <div class="container">
    @if(isset($tumFirsatlar) && $tumFirsatlar->count() > 0)
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:20px; max-width:1100px; margin:0 auto;">
        @foreach($tumFirsatlar as $firsat)
          @php
            $indirim = (float)($firsat->indirim ?? 0);
            $fiyat = (float)($firsat->fiyat ?? $firsat->tutar ?? 0);
            $indirimliFiyat = $fiyat > 0 ? ($fiyat - ($fiyat * $indirim / 100)) : 0;
            $fiyatGoster = $fiyat > 0 ? \App\Helpers\DovizKuruHelper::fiyatGoster($fiyat, $currency) : 0;
            $indirimliFiyatGoster = $indirimliFiyat > 0 ? \App\Helpers\DovizKuruHelper::fiyatGoster($indirimliFiyat, $currency) : 0;

            if ($firsat->tip == 'paket') {
                $resim_yolu = get_package_image_path($firsat->resim ?? null);
            } else {
                if ($firsat->resim) {
                    $resim_path = $firsat->resim;
                    if (strpos($resim_path, 'tema/') === 0 || strpos($resim_path, '/tema/') === 0) {
                        $resim_yolu = asset($resim_path);
                    } else {
                        $resim_yolu = asset('tema/uploads/kampanyalar/' . $resim_path);
                    }
                } else {
                    $resim_yolu = asset('tema/img/noimage.png');
                }
            }

            // İncele linki: paket -> paket detay, kampanya -> fırsat detay (seo varsa), diğer -> link/paketler
            if ($firsat->tip == 'paket' && !empty($firsat->seo)) {
                $detayLink = route('paket.detay', $firsat->seo);
            } elseif ($firsat->tip == 'paket') {
                $detayLink = route('paket.detay', $firsat->id);
            } elseif ($firsat->tip == 'kampanya' && !empty($firsat->seo)) {
                $detayLink = route('firsat.detay', $firsat->seo);
            } else {
                $detayLink = $firsat->link ?? route('paketler');
            }
          @endphp

          <div style="background:#fff; border:1px solid #ececec; border-radius:14px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; flex-direction:column; transition:box-shadow .25s ease, transform .25s ease;" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.10)'; this.style.transform='translateY(-4px)';" onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'; this.style.transform='translateY(0)';">

            <div style="position:relative; height:180px; overflow:hidden; background:#f0f0ec;">
              @if($firsat->tip == 'crm_firsat')
              <div style="width:100%; height:100%; background:linear-gradient(135deg, rgba(184,182,46,.12) 0%, rgba(184,182,46,.04) 100%); display:flex; align-items:center; justify-content:center;">
                <i class="mdi mdi-tag-multiple" style="font-size:56px; color:#b8b62e; opacity:.85;"></i>
              </div>
              <span style="position:absolute; top:12px; left:12px; background:#b8b62e; color:#1a1a0e; padding:6px 14px; border-radius:8px; font-weight:700; font-size:13px;">{{ __('messages.opportunities_title') }}</span>
              @else
              <img src="{{ $resim_yolu }}" alt="{{ $firsat->adi ?? __('messages.opportunities_title') }}" style="width:100%; height:100%; object-fit:cover; display:block;" onerror="this.style.display='none'; this.parentElement.style.background='#f0f0ec';">
              @if($indirim > 0)
              <span style="position:absolute; top:12px; left:12px; background:#b8b62e; color:#1a1a0e; padding:6px 14px; border-radius:8px; font-weight:700; font-size:13px;">%{{ $indirim }} {{ __('messages.discount') }}</span>
              @endif
              @endif
            </div>

            <div style="padding:20px; display:flex; flex-direction:column; flex:1;">
              <h3 style="color:#1a1a1a; font-size:18px; font-weight:600; margin:0 0 8px; line-height:1.3;">
                <a href="{{ $detayLink }}" style="color:#1a1a1a; text-decoration:none;">{{ $firsat->adi ?? __('messages.opportunities_title') }}</a>
              </h3>

              @if($firsat->aciklama)
              <p style="color:#777; font-size:14px; line-height:1.6; margin:0 0 14px; flex:1;">{{ Str::limit($firsat->aciklama, 90) }}</p>
              @else
              <div style="flex:1;"></div>
              @endif

              @if($fiyat > 0)
              <div style="display:flex; align-items:baseline; gap:10px; margin-bottom:14px;">
                @if($indirim > 0)
                <span style="color:#bbb; font-size:15px; text-decoration:line-through;">{{ $sembol }}{{ $fiyatGoster }}</span>
                <span style="color:#1a1a1a; font-size:24px; font-weight:700;">{{ $sembol }}{{ $indirimliFiyatGoster }}</span>
                @else
                <span style="color:#1a1a1a; font-size:24px; font-weight:700;">{{ $sembol }}{{ $fiyatGoster }}</span>
                @endif
              </div>
              @endif

              <div style="display:flex; gap:10px; margin-top:auto;">
                <a href="{{ $detayLink }}" style="flex:1; display:flex; align-items:center; justify-content:center; gap:7px; padding:11px; border-radius:10px; border:1.5px solid #e0e0d8; background:#fff; color:#555; font-weight:600; font-size:14px; text-decoration:none; transition:all .2s ease;" onmouseover="this.style.borderColor='#b8b62e'; this.style.color='#1a1a1a';" onmouseout="this.style.borderColor='#e0e0d8'; this.style.color='#555';">
                  <i class="mdi mdi-eye" style="font-size:16px;"></i> {{ __('messages.view_details') }}
                </a>
                @if($firsat->tip == 'paket' && $fiyat > 0)
                <a href="{{ route('web.paket.satinal', $firsat->id) }}" style="flex:1; display:flex; align-items:center; justify-content:center; gap:7px; padding:11px; border-radius:10px; border:none; background:#b8b62e; color:#1a1a0e; font-weight:600; font-size:14px; text-decoration:none; transition:background .2s ease;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';">
                  <i class="mdi mdi-cart" style="font-size:16px;"></i> {{ __('messages.buy_now') }}
                </a>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div style="max-width:560px; margin:30px auto 0; background:#fff; border:1px solid #ececec; border-radius:14px; padding:52px 40px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <div style="width:72px; height:72px; margin:0 auto 20px; border-radius:50%; background:rgba(184,182,46,.12); display:flex; align-items:center; justify-content:center;">
          <i class="mdi mdi-tag-off-outline" style="font-size:34px; color:#b8b62e;"></i>
        </div>
        <h5 style="color:#1a1a1a; font-size:20px; font-weight:700; margin:0 0 10px;">{{ __('messages.no_active_opportunity') }}</h5>
        <p style="color:#999; font-size:15px; margin:0 0 22px;">{{ __('messages.no_opportunity_note') }}</p>
        <a href="{{ route('paketler') }}" style="display:inline-flex; align-items:center; gap:8px; padding:12px 26px; background:#b8b62e; color:#1a1a0e; font-weight:600; font-size:14px; border-radius:10px; text-decoration:none; transition:background .2s ease;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';">
          <i class="mdi mdi-package-variant"></i> {{ __('messages.browse_packages_button') }}
        </a>
      </div>
    @endif
  </div>
</div>
@endsection