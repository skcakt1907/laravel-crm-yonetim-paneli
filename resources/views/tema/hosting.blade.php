@extends('layouts.master')

@section('title', $kategori->adi ?? __('messages.web_hosting'))

@section('head')
<link rel="stylesheet" href="{{ asset('tema/css/paketler.css') }}">
@endsection

@section('content')
@php
    use Illuminate\Support\Facades\DB;
    $moduller = DB::table('moduller')->first();
    $currency = request('currency') ?? session('currency', 'TRY');
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
      <a href="{{ route('hosting') }}" style="color:#999; text-decoration:none;">{{ __('messages.web_hosting') }}</a>
      <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
      <span style="color:#1a1a1a; font-weight:600;">{{ $kategori->adi }}</span>
    </div>
    <h1 style="color:#1a1a1a; font-size:28px; font-weight:700; margin:0;">{{ $kategori->adi }}</h1>
    <p style="color:#999; font-size:14px; margin:6px 0 0;">{{ $kategori->kisa ?? __('messages.web_hosting') }}</p>
  </div>
</div>

@if(request('durum') == 'success')
<script>
  Swal.fire({
    type: 'success',
    title: 'Başarılı',
    text: 'Hosting sepetinize eklendi',
    confirmButtonText: 'Tamam',
    timer: 5000
  });
</script>
@endif

<div style="background:#f0f0ec; padding: 0 0 70px;">
  <div class="container">
    @if($hostingler->count() > 0)
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(250px, 1fr)); gap:20px; max-width:1100px; margin:0 auto;">
        @foreach($hostingler as $hosting)
          @php
            $tutar = (float)($hosting->tutar ?? $hosting->fiyat ?? 0);
            $fiyat = \App\Helpers\DovizKuruHelper::fiyatGoster($tutar, $currency);
            $zmnt_text = match($hosting->zmnt ?? 0) {
              0 => __('messages.monthly'),
              1 => __('messages.yearly'),
              2 => __('messages.quarterly'),
              default => ''
            };
          @endphp
          <div style="background:#fff; border:1px solid #ececec; border-radius:14px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; flex-direction:column; transition:box-shadow .25s ease, transform .25s ease;" onmouseover="this.style.boxShadow='0 8px 24px rgba(0,0,0,0.10)'; this.style.transform='translateY(-4px)';" onmouseout="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'; this.style.transform='translateY(0)';">

            <div style="display:flex; align-items:center; gap:10px; padding:18px 20px; border-bottom:1px solid #f0f0ec;">
              <div style="width:40px; height:40px; border-radius:10px; background:rgba(184,182,46,.12); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="mdi mdi-server" style="font-size:21px; color:#9a981f;"></i>
              </div>
              <div style="font-size:16px; font-weight:600; color:#1a1a1a; line-height:1.2;">{{ $hosting->adi }}</div>
              <span style="margin-left:auto; width:24px; height:24px; border-radius:50%; background:rgba(184,182,46,.15); display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                <i class="mdi mdi-check" style="font-size:14px; color:#9a981f;"></i>
              </span>
            </div>

            <div style="text-align:center; padding:24px 20px; background:rgba(184,182,46,.06);">
              <div style="font-size:30px; font-weight:700; color:#1a1a1a; line-height:1;">{{ $sembol }}{{ $fiyat }}</div>
              <div style="font-size:13px; color:#999; margin-top:6px;">/ {{ $zmnt_text }}</div>
            </div>

            <div style="padding:18px 20px; display:flex; flex-direction:column; flex:1;">
              @if($hosting->ozellikler)
                @php $ozellikler = preg_split('/,/', $hosting->ozellikler, -1, PREG_SPLIT_NO_EMPTY); @endphp
                <div style="flex:1;">
                  @foreach($ozellikler as $ozellik)
                    <div style="display:flex; align-items:center; gap:9px; padding:7px 0; font-size:14px; color:#555; border-bottom:1px solid #f7f7f4;">
                      <i class="mdi mdi-check-circle" style="font-size:16px; color:#b8b62e; flex-shrink:0;"></i>
                      <span>{{ trim($ozellik) }}</span>
                    </div>
                  @endforeach
                </div>
              @endif
              @if($moduller && isset($moduller->alan6) && $moduller->alan6 == "1")
                <a href="{{ route('hosting.satinal', $hosting->id) }}" style="display:flex; align-items:center; justify-content:center; gap:8px; width:100%; margin-top:16px; padding:12px; border-radius:10px; border:none; background:#b8b62e; color:#1a1a0e; font-weight:600; font-size:14px; text-decoration:none; transition:background .2s ease;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';">
                  <i class="mdi mdi-cart" style="font-size:16px;"></i> {{ __('messages.add_to_cart') }}
                </a>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @else
      <div style="max-width:560px; margin:30px auto 0; background:#fff; border:1px solid #ececec; border-radius:14px; padding:52px 40px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <div style="width:72px; height:72px; margin:0 auto 20px; border-radius:50%; background:rgba(184,182,46,.12); display:flex; align-items:center; justify-content:center;">
          <i class="mdi mdi-server" style="font-size:34px; color:#b8b62e;"></i>
        </div>
        <h5 style="color:#1a1a1a; font-size:20px; font-weight:700; margin:0 0 10px;">{{ __('messages.no_items_found') }}</h5>
        <p style="color:#999; font-size:15px; margin:0;">{{ __('messages.no_hosting_packages_in_category') }}</p>
      </div>
    @endif
  </div>
</div>

@if($kategori->aciklama)
<section id="features" style="padding:50px 0; background:#fff; border-top:1px solid #f0f0ec;">
  <div class="container">
    <div class="info-content" style="max-width:900px; margin:0 auto; color:#555; line-height:1.8;">
      {!! $kategori->aciklama !!}
    </div>
  </div>
</section>
@endif

@endsection