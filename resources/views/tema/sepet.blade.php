@extends('layouts.master')

@section('title', __('messages.my_cart'))

@section('content')

<!-- ***** BAŞLIK + BREADCRUMB ***** -->
<div style="background:#fff; padding: 110px 0 26px; border-bottom:1px solid #e8e8e2;">
    <div class="container">
        <div style="font-size:13px; color:#999; margin-bottom:10px;">
            <a href="{{ route('anasayfa') }}" style="color:#999; text-decoration:none;">{{ __('messages.home') ?? 'Ana Sayfa' }}</a>
            <span style="color:#b8b62e; margin:0 6px;">&rsaquo;</span>
            <span style="color:#1a1a1a; font-weight:600;">{{ __('messages.my_cart') ?? 'Sepetim' }}</span>
        </div>
        <h1 style="color:#1a1a1a; font-size:28px; font-weight:700; margin:0;">{{ __('messages.my_cart') ?? 'Sepetim' }}</h1>
    </div>
</div>

<!-- Bildirimler -->
<div class="sepet-notification" style="position: fixed; top: 20px; right: 20px; z-index: 100000; max-width: 400px; width: 100%; pointer-events: none;">
    <div style="pointer-events: auto;">
        @if(session('error'))
            <div class="alert" style="display:flex; align-items:center; gap:8px; padding:12px 16px; border-radius:10px; background:#fee; border:1px solid #fcc; color:#c33; margin-bottom:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                <i class="mdi mdi-alert-circle" style="font-size:20px;"></i><span style="flex:1;">{{ session('error') }}</span>
            </div>
        @endif
        @if(session('success'))
            <div class="alert" style="display:flex; align-items:center; gap:8px; padding:12px 16px; border-radius:10px; background:#ecfdf5; border:1px solid #a7f3d0; color:#047857; margin-bottom:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                <i class="mdi mdi-check-circle" style="font-size:20px;"></i><span style="flex:1;">{{ session('success') }}</span>
            </div>
        @endif
        @if(!session('error') && request('kupon_error') == '1')
            <div class="alert" style="display:flex; align-items:center; gap:8px; padding:12px 16px; border-radius:10px; background:#fee; border:1px solid #fcc; color:#c33; margin-bottom:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                <i class="mdi mdi-alert-circle" style="font-size:20px;"></i><span style="flex:1;">{{ __('messages.invalid_coupon') }}</span>
            </div>
        @endif
        @if(!session('error') && request('bayi_error') == '1')
            <div class="alert" style="display:flex; align-items:center; gap:8px; padding:12px 16px; border-radius:10px; background:#fee; border:1px solid #fcc; color:#c33; margin-bottom:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                <i class="mdi mdi-alert-circle" style="font-size:20px;"></i><span style="flex:1;">{{ __('messages.invalid_reseller_code') }}</span>
            </div>
        @endif
        @if($errors->any())
            <div class="alert" style="display:flex; align-items:center; gap:8px; padding:12px 16px; border-radius:10px; background:#fffbeb; border:1px solid #fde68a; color:#92400e; margin-bottom:10px; box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                <i class="mdi mdi-alert" style="font-size:20px;"></i><span style="flex:1;">{{ $errors->first() }}</span>
            </div>
        @endif
    </div>
</div>

<!-- ***** İÇERİK ***** -->
<div style="background:#f0f0ec; padding: 40px 0 70px;">
    <div class="container">
        @if($sepet && count($sepet) > 0)
        <div style="display:grid; grid-template-columns: minmax(0, 2.2fr) minmax(340px, 1fr); gap:28px; max-width:1280px; margin:0 auto; align-items:start;">

            <!-- SOL: SEPET İÇERİĞİ -->
            <div style="background:#fff; border:1px solid #ececec; border-radius:16px; padding:28px; box-shadow:0 2px 8px rgba(0,0,0,0.06); display:flex; flex-direction:column;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; padding-bottom:14px; border-bottom:2px solid #b8b62e;">
                    <h5 style="color:#1a1a1a; font-size:20px; font-weight:700; margin:0;">
                        <i class="mdi mdi-basket" style="color:#b8b62e;"></i> {{ __('messages.cart_content') ?? 'Sepet İçeriği' }}
                    </h5>
                    <span style="background:rgba(184,182,46,.12); color:#9a981f; font-size:13px; font-weight:700; padding:4px 12px; border-radius:20px;">{{ count($sepet) }} ürün</span>
                </div>

                <!-- Ürün kartları -->
                <div style="display:flex; flex-direction:column; gap:14px;">
                    @foreach($sepet as $item)
                    @php
                        $fiyat = (float) ($item->fiyat ?? $item->tutar ?? $item->price ?? $item->ucret ?? 0);
                        $miktar = (int) ($item->miktar ?? 1);
                        if ($miktar < 1) $miktar = 1;
                        $tip = strtolower($item->urun_tipi ?? $item->aciklama ?? '');
                        if (str_contains($tip, 'hosting')) { $ikon='mdi-server'; $rozet=$item->aciklama ?? 'Hosting'; }
                        elseif (str_contains($tip, 'domain') || str_contains($tip, 'alan')) { $ikon='mdi-web'; $rozet=$item->aciklama ?? 'Domain'; }
                        elseif (str_contains($tip, 'paket')) { $ikon='mdi-package-variant-closed'; $rozet=$item->aciklama ?? 'Web Paketi'; }
                        else { $ikon='mdi-cart'; $rozet=$item->aciklama ?? 'Ürün'; }
                    @endphp
                    <div style="display:flex; align-items:center; gap:16px; padding:18px; border:1px solid #ececec; border-radius:14px; background:#fff; transition:box-shadow .2s ease;" onmouseover="this.style.boxShadow='0 4px 14px rgba(0,0,0,0.07)';" onmouseout="this.style.boxShadow='none';">
                        <!-- İkon -->
                        <div style="width:54px; height:54px; flex-shrink:0; border-radius:12px; background:rgba(184,182,46,.12); display:flex; align-items:center; justify-content:center;">
                            <i class="mdi {{ $ikon }}" style="font-size:26px; color:#9a981f;"></i>
                        </div>
                        <!-- Bilgi -->
                        <div style="flex:1; min-width:0;">
                            <strong style="color:#1a1a1a; font-size:16px; display:block; margin-bottom:5px;">{{ $item->urun_adi }}</strong>
                            <span style="display:inline-flex; align-items:center; gap:5px; background:rgba(184,182,46,.10); color:#9a981f; font-size:11px; font-weight:700; padding:3px 10px; border-radius:6px; text-transform:uppercase; letter-spacing:.3px;">
                                <i class="mdi {{ $ikon }}" style="font-size:13px;"></i> {{ Str::limit($rozet, 26) }}
                            </span>
                        </div>
                        <!-- Miktar -->
                        <div style="display:inline-flex; align-items:center; border:1px solid #e0e0d8; border-radius:10px; overflow:hidden; background:#f7f7f4; flex-shrink:0;">
                            <button type="button" class="miktar-btn miktar-azalt" data-id="{{ $item->id }}" {{ $miktar <= 1 ? 'disabled' : '' }} title="Azalt"
                                style="width:32px; height:36px; border:none; background:transparent; color:{{ $miktar <= 1 ? '#ccc' : '#9a981f' }}; font-size:17px; cursor:{{ $miktar <= 1 ? 'not-allowed' : 'pointer' }}; display:flex; align-items:center; justify-content:center;"
                                onmouseover="if(!this.disabled){this.style.background='rgba(184,182,46,.12)';}" onmouseout="this.style.background='transparent';"><i class="mdi mdi-minus"></i></button>
                            <span class="miktar-deger" data-id="{{ $item->id }}" style="min-width:36px; text-align:center; font-weight:700; color:#1a1a1a; font-size:15px; padding:0 4px;">{{ $miktar }}</span>
                            <button type="button" class="miktar-btn miktar-artir" data-id="{{ $item->id }}" title="{{ __('messages.increase') }}"
                                style="width:32px; height:36px; border:none; background:transparent; color:#9a981f; font-size:17px; cursor:pointer; display:flex; align-items:center; justify-content:center;"
                                onmouseover="this.style.background='rgba(184,182,46,.12)';" onmouseout="this.style.background='transparent';"><i class="mdi mdi-plus"></i></button>
                        </div>
                        <!-- Fiyat -->
                        <div style="text-align:right; min-width:100px; flex-shrink:0;">
                            <strong class="satir-fiyat" data-id="{{ $item->id }}" data-birim="{{ $fiyat }}" style="color:#9a981f; font-size:17px; white-space:nowrap;">{{ \App\Helpers\CurrencyHelper::format($fiyat * $miktar, session('currency', 'TRY')) }}</strong>
                            @if($fiyat == 0)
                            <small style="color:#ef4444; display:block; font-size:11px; margin-top:3px;">({{ __('messages.price_not_loaded') ?? 'Fiyat yüklenemedi' }})</small>
                            @endif
                        </div>
                        <!-- Sil -->
                        <form action="{{ route('sepet.sil', $item->id) }}" method="POST" class="sepet-sil-form" data-urun="{{ $item->urun_adi }}" style="display:inline; margin:0; flex-shrink:0;">
                            @csrf @method('DELETE')
                            <button type="submit" title="{{ __('messages.decrease') }}"
                                style="background:rgba(239,68,68,0.08); border:1px solid rgba(239,68,68,0.25); color:#ef4444; width:38px; height:38px; border-radius:10px; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition:all .2s ease;"
                                onmouseover="this.style.background='rgba(239,68,68,0.18)'; this.style.borderColor='#ef4444';"
                                onmouseout="this.style.background='rgba(239,68,68,0.08)'; this.style.borderColor='rgba(239,68,68,0.25)';"><i class="mdi mdi-trash-can-outline" style="font-size:18px;"></i></button>
                        </form>
                    </div>
                    @endforeach
                </div>

                <!-- Alışverişe devam -->
                <div style="margin-top:22px;">
                    <a href="{{ localized_route('paketler') }}" style="display:inline-flex; align-items:center; gap:7px; padding:11px 22px; border:1.5px solid #e0e0d8; border-radius:10px; background:#fff; color:#555; font-weight:600; font-size:14px; text-decoration:none; transition:all .2s ease;" onmouseover="this.style.borderColor='#b8b62e'; this.style.color='#1a1a1a';" onmouseout="this.style.borderColor='#e0e0d8'; this.style.color='#555';">
                        <i class="mdi mdi-arrow-left"></i> {{ __('messages.continue_shopping') ?? 'Alışverişe Devam Et' }}
                    </a>
                </div>

                <!-- Güven rozetleri (alt boşluğu doldurur) -->
                <div style="margin-top:26px; padding-top:22px; border-top:1px solid #f0f0ec; display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:14px;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:40px; height:40px; border-radius:10px; background:rgba(16,185,129,.10); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="mdi mdi-lock" style="font-size:20px; color:#10b981;"></i></div>
                        <div><strong style="display:block; color:#1a1a1a; font-size:13px;">{{ __('messages.secure_shopping') }}</strong><span style="color:#999; font-size:11px;">{{ __('messages.ssl_secured') }}</span></div>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:40px; height:40px; border-radius:10px; background:rgba(184,182,46,.12); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="mdi mdi-phone" style="font-size:20px; color:#9a981f;"></i></div>
                        <div><strong style="display:block; color:#1a1a1a; font-size:13px;">7/24 Destek</strong><span style="color:#999; font-size:11px;">{{ __('messages.always_with_you') }}</span></div>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="width:40px; height:40px; border-radius:10px; background:rgba(59,130,246,.10); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="mdi mdi-flash" style="font-size:20px; color:#3b82f6;"></i></div>
                        <div><strong style="display:block; color:#1a1a1a; font-size:13px;">{{ __('messages.fast_setup') }}</strong><span style="color:#999; font-size:11px;">{{ __('messages.instant_activation') }}</span></div>
                    </div>
                </div>

                {{-- ═══ ÖNERİLEN PAKETLER (madde 1+2) — sepet içeriğinin altında, sol kolon içinde ═══ --}}
                @if(isset($onerilen_paketler) && $onerilen_paketler->count() > 0)
                <div style="margin-top:26px; padding-top:22px; border-top:1px solid #f0f0ec;">
                    <h5 style="color:#1a1a1a; font-size:17px; font-weight:700; margin:0 0 4px; display:flex; align-items:center; gap:8px;">
                        <i class="mdi mdi-star-four-points" style="color:#b8b62e;"></i> {{ __('messages.you_may_also_like') }}
                    </h5>
                    <p style="color:#999; font-size:13px; margin:0 0 16px;">{{ __('messages.you_may_also_like_sub') }}</p>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:12px;">
                        @foreach($onerilen_paketler as $op)
                            @php
                                $opFiyat = (float) ($op->tutar ?? 0);
                                $opResim = null;
                                if (!empty($op->resim)) {
                                    foreach (['tema/uploads/webpaketleri/kapak/', 'tema/uploads/webpaketleri/', 'tema/uploads/webpaketleri/kucuk/'] as $dir) {
                                        if (file_exists(public_path($dir . $op->resim))) { $opResim = asset($dir . $op->resim); break; }
                                    }
                                }
                            @endphp
                            <div style="border:1px solid #f0f0e8; border-radius:12px; overflow:hidden; display:flex; flex-direction:column; transition:all .2s ease; background:#fff;"
                                 onmouseover="this.style.boxShadow='0 8px 20px rgba(184,182,46,.18)'; this.style.transform='translateY(-3px)';"
                                 onmouseout="this.style.boxShadow='none'; this.style.transform='translateY(0)';">
                                @if($opResim)
                                <div style="height:88px; background:#f7f7f0 url('{{ $opResim }}') center/cover no-repeat;"></div>
                                @else
                                <div style="height:88px; background:linear-gradient(135deg,rgba(184,182,46,.12),rgba(184,182,46,.04)); display:flex; align-items:center; justify-content:center;">
                                    <i class="mdi mdi-package-variant" style="font-size:34px; color:#b8b62e; opacity:.5;"></i>
                                </div>
                                @endif
                                <div style="padding:11px; display:flex; flex-direction:column; gap:7px; flex:1;">
                                    <strong style="color:#1a1a1a; font-size:13px; line-height:1.35; height:36px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">{{ $op->adi }}</strong>
                                    <div style="color:#9a981f; font-weight:800; font-size:15px; margin-top:auto;">{{ \App\Helpers\CurrencyHelper::format($opFiyat, session('currency', 'TRY')) }}</div>
                                    @auth('uye')
                                    <form action="{{ route('sepet.ekle') }}" method="POST" style="margin:0;">
                                        @csrf
                                        <input type="hidden" name="urun_id" value="{{ $op->id }}">
                                        <input type="hidden" name="urun_tipi" value="web-paket">
                                        <input type="hidden" name="urun_adi" value="{{ $op->adi }}">
                                        <input type="hidden" name="fiyat" value="{{ $opFiyat }}">
                                        <input type="hidden" name="miktar" value="1">
                                        <button type="submit" style="width:100%; padding:7px; background:#b8b62e; color:#1a1a0e; border:none; border-radius:8px; font-weight:700; font-size:12px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:5px;"
                                                onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';">
                                            <i class="mdi mdi-cart-plus"></i> {{ __('messages.add_to_cart') }}
                                        </button>
                                    </form>
                                    @else
                                    <a href="{{ localized_route('giris') }}?redirect=sepet" style="width:100%; padding:7px; background:#b8b62e; color:#1a1a0e; border-radius:8px; font-weight:700; font-size:12px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:5px;">
                                        <i class="mdi mdi-cart-plus"></i> {{ __('messages.add_to_cart') }}
                                    </a>
                                    @endauth
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>


            <!-- SAĞ: SİPARİŞ ÖZETİ -->
            <div style="display:flex; flex-direction:column; gap:20px;">
                <div style="background:#fff; border:1px solid #ececec; border-radius:16px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                    <h5 style="color:#1a1a1a; font-size:18px; font-weight:700; margin:0 0 20px; padding-bottom:12px; border-bottom:2px solid #b8b62e;">
                        <i class="mdi mdi-receipt" style="color:#b8b62e;"></i> {{ __('messages.order_summary') ?? 'Sipariş Özeti' }}
                    </h5>

                    @auth('uye')
                    <!-- Kupon -->
                    <div style="background:rgba(184,182,46,.06); padding:16px; border-radius:12px; margin-bottom:14px; border:1px solid rgba(184,182,46,.25);">
                        <form action="{{ route('sepet.kupon.uygula') }}" method="POST">
                            @csrf
                            <label style="color:#5f5e2a; font-size:13px; font-weight:700; margin-bottom:8px; display:block;"><i class="mdi mdi-tag"></i> {{ __('messages.discount_coupon') ?? 'İndirim Kuponu' }}</label>
                            <div style="display:flex; gap:8px;">
                                <input type="text" name="kupon_kod" value="{{ $kupon_kod }}" placeholder="{{ __('messages.enter_coupon_code') ?? 'Kupon kodu girin' }}" style="flex:1; min-width:0; padding:10px 12px; border:1px solid #e0e0d8; border-radius:8px; background:#fff; color:#1a1a1a; text-transform:uppercase; font-size:14px; outline:none;" onfocus="this.style.borderColor='#b8b62e';" onblur="this.style.borderColor='#e0e0d8';">
                                <button type="submit" style="padding:10px 16px; background:#b8b62e; color:#1a1a0e; border:none; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; white-space:nowrap; flex-shrink:0;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';"><i class="mdi mdi-check"></i> {{ __('messages.apply') ?? 'Uygula' }}</button>
                            </div>
                        </form>
                        @if($kupon_kod)
                            @if($kupon_indirim > 0)
                            <div style="margin-top:10px; padding:10px; background:#ecfdf5; border-radius:8px; display:flex; align-items:center; justify-content:space-between; gap:8px;">
                                <span style="color:#047857; font-size:13px; font-weight:600;"><i class="mdi mdi-check-circle"></i> {{ $kupon_mesaj }} <strong>{{ \App\Helpers\CurrencyHelper::format($kupon_indirim, session('currency', 'TRY')) }}</strong></span>
                                <form action="{{ route('sepet.kupon.kaldir') }}" method="POST" style="display:inline; margin:0;">@csrf<button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:12px; white-space:nowrap;"><i class="mdi mdi-close"></i> {{ __('messages.remove') ?? 'Kaldır' }}</button></form>
                            </div>
                            @else
                            <div style="margin-top:10px; padding:10px; background:#fee; border-radius:8px;"><span style="color:#c33; font-size:13px;"><i class="mdi mdi-alert-circle"></i> {{ $kupon_mesaj }}</span></div>
                            @endif
                        @endif
                    </div>

                    {{-- Bayi Kodu kutusu gizlendi (istek üzerine). Geri açmak için bu yorum işaretlerini kaldırın. --}}
                    {{--
                    <!-- Bayi Kodu -->
                    <div style="background:rgba(184,182,46,.06); padding:16px; border-radius:12px; margin-bottom:14px; border:1px solid rgba(184,182,46,.25);">
                        <form action="{{ route('sepet.bayi.uygula') }}" method="POST">
                            @csrf
                            <label style="color:#5f5e2a; font-size:13px; font-weight:700; margin-bottom:8px; display:block;"><i class="mdi mdi-account-star"></i> {{ __('messages.reseller_code') ?? 'Bayi Kodu' }}</label>
                            <div style="display:flex; gap:8px;">
                                <input type="text" name="bayi_kodu" value="{{ $bayi_kodu }}" placeholder="{{ __('messages.enter_reseller_code') ?? 'Bayi kodu girin' }}" style="flex:1; min-width:0; padding:10px 12px; border:1px solid #e0e0d8; border-radius:8px; background:#fff; color:#1a1a1a; text-transform:uppercase; font-size:14px; outline:none;" onfocus="this.style.borderColor='#b8b62e';" onblur="this.style.borderColor='#e0e0d8';">
                                <button type="submit" style="padding:10px 16px; background:#b8b62e; color:#1a1a0e; border:none; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; white-space:nowrap; flex-shrink:0;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';"><i class="mdi mdi-check"></i> {{ __('messages.apply') ?? 'Uygula' }}</button>
                            </div>
                        </form>
                        @if($bayi_kodu)
                            @if($bayi_indirim > 0)
                            <div style="margin-top:10px; padding:10px; background:#ecfdf5; border-radius:8px; display:flex; align-items:center; justify-content:space-between; gap:8px;">
                                <span style="color:#047857; font-size:13px; font-weight:600;"><i class="mdi mdi-check-circle"></i> {{ $bayi_mesaj }} <strong>{{ \App\Helpers\CurrencyHelper::format($bayi_indirim, session('currency', 'TRY')) }}</strong></span>
                                <form action="{{ route('sepet.bayi.kaldir') }}" method="POST" style="display:inline; margin:0;">@csrf<button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:12px; white-space:nowrap;"><i class="mdi mdi-close"></i> {{ __('messages.remove') ?? 'Kaldır' }}</button></form>
                            </div>
                            @endif
                        @else
                            <p style="margin:8px 0 0; color:#999; font-size:12px;"><i class="mdi mdi-information-outline"></i> {{ __('messages.reseller_code_hint') ?? 'Bayi kodunuz varsa ekstra indirim kazanın!' }}</p>
                        @endif
                    </div>
                    --}}

                    <!-- Bakiye -->
                    <div style="background:rgba(184,182,46,.06); padding:16px; border-radius:12px; margin-bottom:14px; border:1px solid rgba(184,182,46,.25);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <span style="color:#666; font-size:14px;">{{ __('messages.your_balance') ?? 'Mevcut Bakiyeniz' }}:</span>
                            <strong style="color:#9a981f; font-size:18px;">{{ \App\Helpers\CurrencyHelper::format($bakiye, session('currency', 'TRY')) }}</strong>
                        </div>
                        @if($yeterli_bakiye)
                        <div style="background:#ecfdf5; padding:10px; border-radius:8px; text-align:center;"><i class="mdi mdi-check-circle" style="color:#10b981;"></i> <span style="color:#047857; font-size:13px; font-weight:600;">{{ __('messages.sufficient_balance') ?? 'Yeterli bakiye' }}</span></div>
                        @else
                        <div style="background:#fee; padding:10px; border-radius:8px; text-align:center;"><i class="mdi mdi-alert-circle" style="color:#ef4444;"></i> <span style="color:#c33; font-size:13px; font-weight:600;">{{ __('messages.insufficient_balance') ?? 'Yetersiz bakiye' }}. {{ __('messages.missing') ?? 'Eksik' }}: {{ \App\Helpers\CurrencyHelper::format($indirimli_toplam - $bakiye, session('currency', 'TRY')) }}</span></div>
                        @endif
                    </div>
                    @endauth

                    <!-- Toplam -->
                    <div style="background:rgba(184,182,46,.06); padding:18px; border-radius:12px; margin-bottom:18px; border:1px solid rgba(184,182,46,.25);">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <span style="color:#666; font-size:14px;">{{ __('messages.subtotal') ?? 'Ara Toplam' }}:</span>
                            <span id="sepet-ara-toplam" style="color:#1a1a1a; font-size:16px;">{{ \App\Helpers\CurrencyHelper::format($toplam, session('currency', 'TRY')) }}</span>
                        </div>
                        @if(isset($kupon_indirim) && $kupon_indirim > 0)
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-top:10px; border-top:1px solid #ececec;">
                            <span style="color:#666; font-size:14px;"><i class="mdi mdi-tag" style="color:#b8b62e;"></i> {{ __('messages.coupon_discount') ?? 'Kupon İndirimi' }}:</span>
                            <span style="color:#10b981; font-size:16px;">-{{ \App\Helpers\CurrencyHelper::format($kupon_indirim, session('currency', 'TRY')) }}</span>
                        </div>
                        @endif
                        @if(isset($bayi_indirim) && $bayi_indirim > 0)
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-top:10px; border-top:1px solid #ececec;">
                            <span style="color:#666; font-size:14px;"><i class="mdi mdi-account-star" style="color:#b8b62e;"></i> {{ __('messages.reseller_discount') ?? 'Bayi İndirimi' }} (%{{ number_format($bayi_indirim_orani, 0) }}):</span>
                            <span style="color:#10b981; font-size:16px;">-{{ \App\Helpers\CurrencyHelper::format($bayi_indirim, session('currency', 'TRY')) }}</span>
                        </div>
                        @endif
                        @if(isset($kdv) && $kdv > 0)
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; padding-top:10px; border-top:1px solid #ececec;">
                            <span style="color:#666; font-size:14px;"><i class="mdi mdi-percent" style="color:#b8b62e;"></i> KDV (%{{ $kdv_orani ?? 20 }}):</span>
                            <span id="sepet-kdv" style="color:#1a1a1a; font-size:16px;">{{ \App\Helpers\CurrencyHelper::format($kdv, session('currency', 'TRY')) }}</span>
                        </div>
                        @endif
                        <div style="display:flex; justify-content:space-between; align-items:baseline; gap:10px; padding-top:12px; border-top:2px solid rgba(184,182,46,.4); flex-wrap:nowrap;">
                            <strong style="color:#1a1a1a; font-size:15px; white-space:nowrap;">{{ __('messages.total') ?? 'Toplam' }} (KDV Dahil):</strong>
                            <strong id="sepet-genel-toplam" style="color:#9a981f; font-size:22px; white-space:nowrap; text-align:right;">{{ \App\Helpers\CurrencyHelper::format($genel_toplam, session('currency', 'TRY')) }}</strong>
                        </div>
                    </div>

                    <!-- Ödeme butonları -->
                    @auth('uye')
                    @php
                        // Profil eksik kontrolü (JS modal için) — onayla() ile AYNI mantık
                        $_u = Auth::guard('uye')->user();
                        $_profilEksikler = [];
                        $_bos = fn($v) => $v === null || trim((string) $v) === '';
                        if ((int) ($_u->utipi ?? 0) === 1) {
                            if ($_bos($_u->fatura_unvan ?? null)) $_profilEksikler[] = 'Fatura Ünvanı';
                            if ($_bos($_u->fatura_tc ?? null))    $_profilEksikler[] = 'Vergi No / TC';
                            if ($_bos($_u->fatura_adres ?? null)) $_profilEksikler[] = 'Fatura Adresi';
                        } else {
                            if ($_bos($_u->tc ?? null))    $_profilEksikler[] = 'TC Kimlik No';
                            if ($_bos($_u->ad ?? null))    $_profilEksikler[] = 'Ad';
                            if ($_bos($_u->soyad ?? null)) $_profilEksikler[] = 'Soyad';
                            if ($_bos($_u->adres ?? null)) $_profilEksikler[] = 'Adres';
                        }
                    @endphp
                    @if($yeterli_bakiye)
                    <form action="{{ route('sepet.bakiye.odeme') }}" method="POST" style="margin-bottom:10px;" onsubmit="return odemeKontrol();">
                        @csrf
                        <button type="submit" style="width:100%; padding:14px; background:#b8b62e; color:#1a1a0e; border:none; border-radius:10px; font-weight:700; font-size:15px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';">
                            <i class="mdi mdi-wallet"></i> {{ __('messages.pay_with_balance') ?? 'Bakiye ile Öde' }}
                        </button>
                    </form>
                    @endif
                    {{-- DN Bank Coin ile kısmi/tam ödeme --}}
                    @php $_dnbankBakiye = (float) ($dnbank_bakiye ?? 0); @endphp
                    @if($_dnbankBakiye > 0)
                    @php $_sepetToplam = (float) ($genel_toplam ?? $indirimli_toplam ?? 0); $_maxCoin = min($_dnbankBakiye, $_sepetToplam); @endphp
                    <div style="background:linear-gradient(135deg,#1a2332,#2a3850); border-radius:10px; padding:14px; margin-bottom:10px; color:#fff;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                            <span style="font-size:13px;"><i class="mdi mdi-bank"></i> DN Bank Coin</span>
                            <strong style="font-size:15px;">{{ \App\Helpers\CurrencyHelper::format($_dnbankBakiye, session('currency', 'TRY')) }}</strong>
                        </div>
                        <div style="display:flex; gap:6px; align-items:center;">
                            <input type="number" id="dnbankCoinInput" data-sepet="{{ $_sepetToplam }}" min="0" max="{{ $_maxCoin }}" step="0.01" value="0"
                                   style="flex:1; padding:9px 12px; border:none; border-radius:8px; font-size:14px; color:#1a1a1a;"
                                   placeholder="{{ __('messages.coin_to_use') }}" oninput="dnbankCoinHesapla()">
                            <button type="button" onclick="dnbankCoinTumunu()" style="padding:9px 12px; background:#cddc39; color:#1a2332; border:none; border-radius:8px; font-weight:700; font-size:12px; cursor:pointer; white-space:nowrap;">Tümü</button>
                        </div>
                        <div id="dnbankCoinBilgi" style="font-size:12px; opacity:.85; margin-top:8px; display:none;">
                            <span id="dnbankCoinKullanilan"></span> coin kullanılacak ·
                            Kalan kart ödemesi: <strong id="dnbankCoinKalan"></strong>
                        </div>
                    </div>
                    @endif

                    {{-- ═══ SÖZLEŞMELER (madde 5) ═══ --}}
                    <div style="margin-bottom:14px; padding:14px; background:#fafaf5; border:1px solid #f0f0e8; border-radius:10px;">
                        <label style="display:flex; align-items:flex-start; gap:9px; cursor:pointer; font-size:12.5px; color:#444; line-height:1.5;">
                            <input type="checkbox" id="sozlesmeOnay" style="margin-top:3px; flex-shrink:0; width:16px; height:16px; cursor:pointer;">
                            <span>
                                <a href="{{ route('sayfa.detay', 'uyelik-sozlesmesi') }}" target="_blank" style="color:#9a981f; font-weight:600;">{{ __('messages.membership_agreement_title') }}</a>,
                                <a href="{{ route('sayfa.detay', 'gizlilik-sozlesmesi') }}" target="_blank" style="color:#9a981f; font-weight:600;">{{ __('messages.privacy_agreement') }}</a>,
                                <a href="{{ route('sayfa.detay', 'cerez-politikasi') }}" target="_blank" style="color:#9a981f; font-weight:600;">{{ __('messages.kvkk_cookie_policy') }}</a> ve
                                <a href="{{ route('sayfa.detay', 'hizmet-ve-kullanim-sozlesmesi') }}" target="_blank" style="color:#9a981f; font-weight:600;">{{ __('messages.terms_of_use_agreement') }}</a>ni
                                okudum, onaylıyorum.
                            </span>
                        </label>
                    </div>

                    <form action="{{ route('sepet.onayla') }}" method="POST" style="margin:0;" id="odemeForm" onsubmit="return odemeKontrol();">
                        @csrf
                        <input type="hidden" name="dnbank_coin" id="dnbankCoinHidden" value="0">
                        <button type="submit" style="width:100%; padding:14px; background:#fff; color:#1a1a1a; border:1.5px solid #b8b62e; border-radius:10px; font-weight:700; font-size:15px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:8px; transition:all .2s ease;" onmouseover="this.style.background='rgba(184,182,46,.08)';" onmouseout="this.style.background='#fff';">
                            <i class="mdi mdi-credit-card"></i> <span id="odemeButonText">{{ __('messages.pay_with_card') ?? 'Kredi Kartı ile Öde' }}</span>
                        </button>
                    </form>
                    @if(!$yeterli_bakiye)
                    <a href="{{ route('bakiyem') }}" style="width:100%; margin-top:10px; padding:14px; background:#1a2332; color:#fff; border:none; border-radius:10px; font-weight:700; font-size:15px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <i class="mdi mdi-wallet-plus"></i> {{ __('messages.add_balance') ?? 'Bakiye Yükle' }}
                    </a>
                    @endif
                    @else
                    <a href="{{ localized_route('giris') }}?redirect=sepet" style="width:100%; padding:14px; background:#b8b62e; color:#1a1a0e; border:none; border-radius:10px; font-weight:700; font-size:15px; text-decoration:none; display:flex; align-items:center; justify-content:center; gap:8px;">
                        <i class="mdi mdi-login"></i> {{ __('messages.login') ?? 'Giriş Yap' }}
                    </a>
                    <p style="text-align:center; margin-top:10px; color:#999; font-size:13px;">{{ __('messages.login_to_complete_order') ?? 'Siparişi tamamlamak için giriş yapın' }}</p>
                    @endauth
                </div>
            </div>
        </div>
        @else
        <!-- Boş sepet -->
        <div style="max-width:560px; margin:30px auto 0; background:#fff; border:1px solid #ececec; border-radius:16px; padding:60px 40px; text-align:center; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <div style="width:110px; height:110px; margin:0 auto 26px; border-radius:50%; background:rgba(184,182,46,.14); display:flex; align-items:center; justify-content:center; position:relative;">
                <svg xmlns="http://www.w3.org/2000/svg" width="58" height="58" viewBox="0 0 24 24" fill="none" stroke="#b8b62e" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" style="display:block;">
                    <circle cx="9" cy="21" r="1.5"></circle>
                    <circle cx="18" cy="21" r="1.5"></circle>
                    <path d="M1 1h3.5l2.6 13.4a2 2 0 0 0 2 1.6h9.1a2 2 0 0 0 2-1.55L22 6H6"></path>
                </svg>
                <!-- Küçük yıldız parıltı vurgusu, modern hava katar -->
                <span style="position:absolute; top:14px; right:18px; width:8px; height:8px; background:#b8b62e; border-radius:50%; opacity:.45;"></span>
                <span style="position:absolute; bottom:18px; left:14px; width:5px; height:5px; background:#b8b62e; border-radius:50%; opacity:.35;"></span>
            </div>
            <h3 style="color:#1a1a1a; font-size:22px; font-weight:700; margin:0 0 12px;">{{ __('messages.cart_empty') ?? 'Sepetiniz boş' }}</h3>
            <p style="color:#999; font-size:15px; margin:0 0 26px;">{{ __('messages.cart_empty_message') ?? 'Sepetinizde henüz ürün bulunmuyor.' }}</p>
            <a href="{{ localized_route('paketler') }}" style="display:inline-flex; align-items:center; gap:8px; padding:13px 28px; background:#b8b62e; color:#1a1a0e; font-weight:700; font-size:15px; border-radius:10px; text-decoration:none;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                {{ __('messages.browse_packages') ?? 'Paketlere Göz At' }}
            </a>
        </div>
        @endif
    </div>
</div>

<script>
// Profil + sözleşme kontrolü (güzel modal ile) — ödeme öncesi
window.__profilEksikler = @json($_profilEksikler ?? []);
window.__profilGitUrl = "{{ route('bilgilerim') }}";

function odemeKontrol() {
    // 1) Profil eksikse → profil modalı (sepette kal, "Profile Git" butonlu)
    if (window.__profilEksikler && window.__profilEksikler.length > 0) {
        sepetModalAc(
            'profil',
            'Profil Bilgileriniz Eksik',
            'Ödeme yapabilmek için profilinizde şu bilgileri tamamlamanız gerekiyor:',
            window.__profilEksikler
        );
        return false;
    }
    // 2) Sözleşme işaretsizse → sözleşme modalı
    var cb = document.getElementById('sozlesmeOnay');
    if (cb && !cb.checked) {
        sepetModalAc(
            'sozlesme',
            'Sözleşme Onayı Gerekli',
            'Devam edebilmek için aşağıdaki sözleşmeleri okuyup onaylamanız gerekmektedir.',
            []
        );
        return false;
    }
    return true;
}

// Eski isim geriye dönük uyum
function sozlesmeKontrol() { return odemeKontrol(); }

// === Güzel modal ===
function sepetModalAc(tip, baslik, mesaj, liste) {
    var overlay = document.getElementById('sepetModalOverlay');
    var ikon = document.getElementById('sepetModalIkon');
    var bas = document.getElementById('sepetModalBaslik');
    var msj = document.getElementById('sepetModalMesaj');
    var listeEl = document.getElementById('sepetModalListe');
    var btnPrimary = document.getElementById('sepetModalBtnPrimary');
    var btnSecondary = document.getElementById('sepetModalBtnSecondary');
    if (!overlay) return;

    bas.textContent = baslik;
    msj.textContent = mesaj;

    // Liste (eksik alanlar)
    if (liste && liste.length > 0) {
        var h = '';
        for (var i = 0; i < liste.length; i++) {
            h += '<li style="padding:6px 0; display:flex; align-items:center; gap:8px; color:#1a1a1a; font-size:14px;"><i class="mdi mdi-alert-circle" style="color:#ef4444;"></i> ' + liste[i] + '</li>';
        }
        listeEl.innerHTML = '<ul style="list-style:none; padding:0; margin:14px 0 0; background:#fff5f5; border:1px solid #fecaca; border-radius:10px; padding:6px 16px;">' + h + '</ul>';
        listeEl.style.display = 'block';
    } else {
        listeEl.innerHTML = '';
        listeEl.style.display = 'none';
    }

    if (tip === 'profil') {
        ikon.className = 'mdi mdi-account-alert';
        ikon.style.color = '#ef4444';
        btnPrimary.innerHTML = '<i class="mdi mdi-account-edit"></i> Profili Tamamla';
        btnPrimary.onclick = function () { window.location.href = window.__profilGitUrl; };
        btnSecondary.textContent = 'Vazgeç';
    } else {
        ikon.className = 'mdi mdi-file-document-alert';
        ikon.style.color = '#f59e0b';
        btnPrimary.innerHTML = '<i class="mdi mdi-check"></i> Anladım';
        btnPrimary.onclick = function () {
            sepetModalKapat();
            var cb = document.getElementById('sozlesmeOnay');
            if (cb) { cb.focus(); cb.parentElement.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
        };
        btnSecondary.textContent = 'Kapat';
    }

    overlay.style.display = 'flex';
    setTimeout(function () { overlay.style.opacity = '1'; document.getElementById('sepetModalBox').style.transform = 'scale(1)'; }, 10);
}

function sepetModalKapat() {
    var overlay = document.getElementById('sepetModalOverlay');
    if (!overlay) return;
    overlay.style.opacity = '0';
    document.getElementById('sepetModalBox').style.transform = 'scale(0.92)';
    setTimeout(function () { overlay.style.display = 'none'; }, 200);
}

document.addEventListener('DOMContentLoaded', function () {
    try {
        const url = new URL(window.location.href);
        let changed = false;
        ['kupon_error','bayi_error'].forEach(function(p){ if(url.searchParams.has(p)){ url.searchParams.delete(p); changed = true; } });
        if (changed) window.history.replaceState({}, '', url.toString());
    } catch (e) {}

    setTimeout(function () {
        document.querySelectorAll('.sepet-notification .alert').forEach(function (el) { el.remove(); });
    }, 5000);

    document.querySelectorAll('.sepet-sil-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var urun = form.getAttribute('data-urun') || 'Bu ürün';

            // SweetAlert hangi sürüm olursa olsun çalışsın diye 3 fallback var:
            // 1) SweetAlert v2 (Swal.fire + Promise + isConfirmed)
            // 2) SweetAlert v1 (swal + callback)
            // 3) Native confirm()
            var submitForm = function () { form.submit(); };

            if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                // SweetAlert v2
                var swalConfig = {
                    title: 'Emin misiniz?',
                    html: '<b>' + urun + '</b> sepetten çıkarılacak.',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#b8b62e',
                    confirmButtonText: 'Evet, çıkar',
                    cancelButtonText: 'Vazgeç',
                    reverseButtons: true
                };
                // icon parametresini destekleyen sürümde göster, desteklemeyende sessiz geç
                try { swalConfig.icon = 'warning'; } catch (err) {}

                var p = Swal.fire(swalConfig);
                if (p && typeof p.then === 'function') {
                    p.then(function (result) {
                        // v2: result.isConfirmed, eski v2: result.value === true
                        if (result && (result.isConfirmed === true || result.value === true)) {
                            submitForm();
                        }
                    }).catch(function () { /* iptal */ });
                }
            } else if (typeof swal === 'function') {
                // SweetAlert v1 (callback API)
                swal({
                    title: 'Emin misiniz?',
                    text: urun + ' sepetten çıkarılacak.',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Evet, çıkar',
                    cancelButtonText: 'Vazgeç',
                    closeOnConfirm: true
                }, function (isConfirm) {
                    if (isConfirm) submitForm();
                });
            } else {
                // Native fallback
                if (confirm(urun + ' sepetten çıkarılsın mı?')) submitForm();
            }
        });
    });

    // Miktar +/- AJAX (sayfa yenilenmeden güncelle)
    var guncelleUrlTemplate = "{{ route('sepet.guncelle', ['id' => 'SEPET_ID']) }}";
    var csrfToken = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

    function miktarGuncelle(id, yeniMiktar, btnGrup) {
        if (yeniMiktar < 1) return;
        var url = guncelleUrlTemplate.replace('SEPET_ID', id);
        // butonları geçici kilitle
        btnGrup.querySelectorAll('.miktar-btn').forEach(function(b){ b.disabled = true; });

        var fd = new FormData();
        fd.append('_method', 'PUT');
        fd.append('_token', csrfToken);
        fd.append('miktar', yeniMiktar);

        fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: fd
        })
        .then(function(r){ return r.json(); })
        .then(function(data){
            if (data && data.success) {
                // miktar değerini güncelle
                var degerEl = document.querySelector('.miktar-deger[data-id="' + id + '"]');
                if (degerEl) degerEl.textContent = data.miktar;
                // satır toplamını güncelle
                var fiyatEl = document.querySelector('.satir-fiyat[data-id="' + id + '"]');
                if (fiyatEl && data.satir_toplam_str) fiyatEl.textContent = data.satir_toplam_str;
                // ara toplamı güncelle
                var araEl = document.getElementById('sepet-ara-toplam');
                if (araEl && data.ara_toplam_str) araEl.textContent = data.ara_toplam_str;
                // genel toplamı (Toplam) güncelle
                var genelEl = document.getElementById('sepet-genel-toplam');
                if (genelEl && data.genel_toplam_str) genelEl.textContent = data.genel_toplam_str;
                // azalt butonunu 1'de pasifle
                var azaltBtn = btnGrup.querySelector('.miktar-azalt');
                if (azaltBtn) {
                    if (data.miktar <= 1) { azaltBtn.disabled = true; azaltBtn.style.color = '#ccc'; azaltBtn.style.cursor = 'not-allowed'; }
                    else { azaltBtn.disabled = false; azaltBtn.style.color = '#9a981f'; azaltBtn.style.cursor = 'pointer'; }
                }
                var artirBtn = btnGrup.querySelector('.miktar-artir');
                if (artirBtn) artirBtn.disabled = false;
            } else {
                btnGrup.querySelectorAll('.miktar-btn').forEach(function(b){ b.disabled = false; });
            }
        })
        .catch(function(){
            btnGrup.querySelectorAll('.miktar-btn').forEach(function(b){ b.disabled = false; });
        });
    }

    document.querySelectorAll('.miktar-artir').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = btn.getAttribute('data-id');
            var degerEl = document.querySelector('.miktar-deger[data-id="' + id + '"]');
            var mevcut = parseInt(degerEl ? degerEl.textContent : '1', 10) || 1;
            miktarGuncelle(id, mevcut + 1, btn.parentElement);
        });
    });
    document.querySelectorAll('.miktar-azalt').forEach(function(btn){
        btn.addEventListener('click', function(){
            if (btn.disabled) return;
            var id = btn.getAttribute('data-id');
            var degerEl = document.querySelector('.miktar-deger[data-id="' + id + '"]');
            var mevcut = parseInt(degerEl ? degerEl.textContent : '1', 10) || 1;
            if (mevcut <= 1) return;
            miktarGuncelle(id, mevcut - 1, btn.parentElement);
        });
    });
});

// === DN Bank Coin kısmi ödeme ===
function _dnbankFmt(v) {
    try { return v.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺'; }
    catch (e) { return v.toFixed(2) + ' ₺'; }
}
function dnbankCoinHesapla() {
    var inp = document.getElementById('dnbankCoinInput');
    var hidden = document.getElementById('dnbankCoinHidden');
    var bilgi = document.getElementById('dnbankCoinBilgi');
    if (!inp || !hidden) return;
    var max = parseFloat(inp.getAttribute('max')) || 0;
    var val = parseFloat(inp.value) || 0;
    if (val < 0) val = 0;
    if (val > max) { val = max; inp.value = max.toFixed(2); }
    hidden.value = val.toFixed(2);
    if (bilgi) {
        if (val > 0) {
            var kalan = max - val; // max = min(coin, sepetToplam) → kalan kart ödemesi sepetToplam - val
            var sepetToplam = parseFloat(inp.getAttribute('data-sepet') || max);
            var kartKalan = sepetToplam - val;
            if (kartKalan < 0) kartKalan = 0;
            document.getElementById('dnbankCoinKullanilan').textContent = _dnbankFmt(val);
            document.getElementById('dnbankCoinKalan').textContent = _dnbankFmt(kartKalan);
            bilgi.style.display = '';
            var btn = document.getElementById('odemeButonText');
            if (btn) btn.textContent = (kartKalan <= 0) ? 'Coin ile Öde' : 'Coin + Kart ile Öde';
        } else {
            bilgi.style.display = 'none';
            var btn2 = document.getElementById('odemeButonText');
            if (btn2) btn2.textContent = 'Kredi Kartı ile Öde';
        }
    }
}
function dnbankCoinTumunu() {
    var inp = document.getElementById('dnbankCoinInput');
    if (!inp) return;
    inp.value = (parseFloat(inp.getAttribute('max')) || 0).toFixed(2);
    dnbankCoinHesapla();
}

</script>

{{-- ═══ GÜZEL MODAL (profil eksik / sözleşme uyarısı) ═══ --}}
<div id="sepetModalOverlay" onclick="if(event.target===this)sepetModalKapat()" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(15,23,42,.55); backdrop-filter:blur(3px); align-items:center; justify-content:center; padding:20px; opacity:0; transition:opacity .2s ease;">
    <div id="sepetModalBox" style="background:#fff; border-radius:18px; max-width:440px; width:100%; padding:28px 26px; box-shadow:0 24px 60px rgba(0,0,0,.3); transform:scale(0.92); transition:transform .2s ease; font-family:'Poppins',system-ui,sans-serif;">
        <div style="display:flex; flex-direction:column; align-items:center; text-align:center;">
            <div style="width:68px; height:68px; border-radius:50%; background:rgba(239,68,68,.1); display:flex; align-items:center; justify-content:center; margin-bottom:16px;">
                <i id="sepetModalIkon" class="mdi mdi-account-alert" style="font-size:36px; color:#ef4444;"></i>
            </div>
            <h3 id="sepetModalBaslik" style="margin:0 0 8px; font-size:19px; font-weight:700; color:#1a1a1a;">{{ __('messages.title') }}</h3>
            <p id="sepetModalMesaj" style="margin:0; font-size:14px; color:#666; line-height:1.55;">{{ __('messages.message') }}</p>
            <div id="sepetModalListe" style="width:100%; display:none;"></div>
        </div>
        <div style="display:flex; gap:10px; margin-top:24px;">
            <button type="button" id="sepetModalBtnSecondary" onclick="sepetModalKapat()" style="flex:1; padding:12px; background:#f3f4f6; color:#444; border:none; border-radius:10px; font-weight:600; font-size:14px; cursor:pointer; transition:background .2s;" onmouseover="this.style.background='#e5e7eb';" onmouseout="this.style.background='#f3f4f6';">{{ __('messages.give_up') }}</button>
            <button type="button" id="sepetModalBtnPrimary" style="flex:1.4; padding:12px; background:#b8b62e; color:#1a1a0e; border:none; border-radius:10px; font-weight:700; font-size:14px; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:background .2s;" onmouseover="this.style.background='#a3a128';" onmouseout="this.style.background='#b8b62e';"><i class="mdi mdi-account-edit"></i> Profili Tamamla</button>
        </div>
    </div>
</div>
@endsection