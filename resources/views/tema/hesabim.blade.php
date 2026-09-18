@extends('layouts.panel')

@section('page_title', 'Hesabım')

@section('panel_top')
<div class="row mb-4 mt-3" style="margin-right:0;margin-left:0">
    {{-- Hizmetlerim = web paketi + hosting + alan adı (satın alınan her şey) --}}
    <div class="col-sm-6 col-6 border-left-3 border-radius col-md tile panelrenk1">
        <a href="{{ Route::has('aldigim.hizmetler') ? route('aldigim.hizmetler') : route('web.paketlerim') }}">
            <div class="icon"><i class="fa fa-cubes"></i></div>
            <div class="stat">{{ $stats['hizmetlerim'] ?? 0 }}</div>
            <div class="title">Hizmetlerim</div>
            <div class="highlight bg-color-blue"></div>
        </a>
    </div>
    <div class="col-sm-6 col-6 border-left-3 border-radius col-md tile panelrenk2">
        <a href="{{ route('faturalarim') }}">
            <div class="icon"><i class="fa fa-check-circle"></i></div>
            <div class="stat">{{ $stats['odenmis_fatura'] ?? 0 }}</div>
            <div class="title">Ödenmiş Faturalar</div>
            <div class="highlight bg-color-green"></div>
        </a>
    </div>
    <div class="col-sm-6 col-6 border-left-3 border-radius col-md tile panelrenk3">
        <a href="{{ route('faturalarim') }}">
            <div class="icon"><i class="fa fa-file"></i></div>
            <div class="stat">{{ $stats['odenmemis_fatura'] ?? 0 }}</div>
            <div class="title">{{ __('messages.panel_unpaid_invoices') }}</div>
            <div class="highlight bg-color-red"></div>
        </a>
    </div>
    <div class="col-sm-6 col-6 border-left-3 border-radius col-md tile panelrenk4">
        <a href="{{ route('destek.talebi.olustur') }}">
            <div class="icon"><i class="fa fa-comments"></i></div>
            <div class="stat">{{ $stats['toplam_destek'] ?? 0 }}</div>
            <div class="title">Destek Talepleri</div>
            <div class="highlight bg-color-red"></div>
        </a>
    </div>
    <div class="col-sm-6 col-6 border-left-3 border-radius col-md tile panelrenk5">
        <a href="{{ Route::has('bakiyem') ? route('bakiyem') : '#' }}">
            <div class="icon"><i class="fa fa-credit-card"></i></div>
            <div class="stat">{{ number_format((float)($stats['bakiye'] ?? 0), 2, ',', '.') }} ₺</div>
            <div class="title">Bakiyem</div>
            <div class="highlight bg-color-gold"></div>
        </a>
    </div>
</div>

@if(($stats['odenmemis_fatura'] ?? 0) > 0)
<div class="alert alert-danger">
    <div class="row">
        <div class="col-md-2"><i class="fa fa-info-circle" style="font-size:91px;text-align:center;display:block;margin-top:20px"></i></div>
        <div class="col-md-10">
            <div class="balanceinfo">
                <h5><strong>{{ __('messages.unpaid_invoices_warning') }}</strong></h5>
                <p style="font-weight:400">
                    <span>{{ __('messages.in_your_account') }} <strong>{{ $stats['odenmemis_fatura'] }} adet</strong> ödenmemiş fatura ({{ number_format((float)($stats['fatura_tutar'] ?? 0), 2, ',', '.') }} ₺) bulunmaktadır. Lütfen kontrol edin.</span>
                </p>
                <a href="{{ route('faturalarim') }}" class="btn btn-sm btn-outline-primary">{{ __('messages.view_my_invoices') }}</a>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('panel_content')
@php
    // Zaten bayi olan müşteriye teşvik banner'ı gösterilmez
    $_uye = \Illuminate\Support\Facades\Auth::guard('uye')->user();
    $_bayiMi = (int) ($_uye->bayi ?? 0) === 1;
    $_bayiLink = \Illuminate\Support\Facades\Route::has('bayi.basvuru') ? route('bayi.basvuru') : null;
@endphp

<div class="row" style="margin-left:0;margin-right:0">
    {{-- SOL: tablolar --}}
    <div class="col-12 {{ (!$_bayiMi && $_bayiLink) ? 'col-xl-8' : '' }} p-0">

<div class="col-md-12 border-left-3 main-content">
    <div class="title-area">
        <h5 class="title"><i class="fa fa-clock-o"></i> Son Destek Talepleri</h5>
        <a href="{{ route('destek.talebi.olustur') }}" class="btn btn-sm btn-outline-primary pull-right">{{ __('messages.new_support_ticket_plus') }}</a>
    </div>
    <table id="destek" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th scope="col" class="text-left">Konu</th>
            <th scope="col" class="text-center" style="width:150px">{{ __('messages.table_status') }}</th>
            <th scope="col" class="text-center" style="width:100px">{{ __('messages.action') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($destekler as $d)
            <tr>
                <th scope="row" class="align-middle">
                    <a href="{{ route('destek.detay', $d->id) }}" class="link">{{ $d->baslik }}</a>
                    <p class="t-detail">{{ $d->hizmet ?? 'Genel' }}</p>
                </th>
                <td class="text-center align-middle">
                    @if($d->durum == 0)
                        <label class="alert alert-danger alert-sm mt-3">Beklemede</label>
                    @elseif($d->durum == 1)
                        <label class="alert alert-success alert-sm mt-3">{{ __('messages.status_answered') }}</label>
                    @elseif($d->durum == 2)
                        <label class="alert alert-info alert-sm mt-3">{{ __('messages.status_customer_reply') }}</label>
                    @else
                        <label class="alert alert-secondary alert-sm mt-3">{{ __('messages.status_closed') }}</label>
                    @endif
                </td>
                <td class="text-center align-middle">
                    <a href="{{ route('destek.detay', $d->id) }}" class="btn btn-outline-primary btn-sm m-0 pl-3 pr-3">
                        <i class="fa fa-search"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center p-4">{{ __('messages.empty_support') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="col-md-12 border-left-3 main-content">
    <div class="title-area">
        <h5 class="title"><i class="fa fa-clock-o"></i> {{ __('messages.recent_invoices') }}</h5>
        <a href="{{ route('faturalarim') }}" class="btn btn-sm btn-outline-primary pull-right">{{ __('messages.see_all') }}</a>
    </div>
    <table id="son_siparis" class="table table-bordered table-striped">
        <thead>
        <tr>
            <th scope="col" class="text-left">{{ __('messages.invoice') }}</th>
            <th scope="col" class="text-center" style="width:150px">{{ __('messages.table_status') }}</th>
            <th scope="col" class="text-center" style="width:100px">{{ __('messages.action') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($faturalar as $f)
            <tr>
                <th scope="row" class="align-middle">
                    <strong>{{ $f->fatura_no ?? ('FAT-'.str_pad($f->id, 6, '0', STR_PAD_LEFT)) }}</strong>
                    <p class="t-detail">{{ number_format((float)($f->tutar ?? 0), 2, ',', '.') }} ₺</p>
                </th>
                <td class="text-center align-middle">
                    @if($f->durum == 0)
                        <label class="alert alert-danger alert-sm mt-3">{{ __('messages.unpaid') }}</label>
                    @else
                        <label class="alert alert-success alert-sm mt-3">{{ __('messages.paid') }}</label>
                    @endif
                </td>
                <td class="text-center align-middle">
                    <a href="{{ route('fatura.detay', $f->id) }}" class="btn btn-outline-primary btn-sm m-0 pl-3 pr-3">
                        <i class="fa fa-search"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="text-center p-4">{{ __('messages.empty_invoices') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

    </div>{{-- /SOL --}}

    {{-- SAĞ: Bayi teşvik banner'ı (boş alana) --}}
    @if(!$_bayiMi && $_bayiLink)
    <div class="col-12 col-xl-4 pl-xl-3 p-0">
        <div class="bayi-tesvik">
            <div class="bt-rozet">İŞ ORTAKLIĞI</div>
            <div class="bt-ikon"><i class="fa fa-handshake-o"></i></div>

            <h4 class="bt-baslik">Bayimiz Olun,<br>Birlikte Kazanalım</h4>
            <p class="bt-metin">
                DN Kreatif bayisi olarak web sitesi, hosting, alan adı ve dijital hizmetleri
                kendi müşterilerinize <strong>özel bayi fiyatlarıyla</strong> sunun.
            </p>

            <ul class="bt-liste">
                <li><i class="fa fa-check"></i> Özel bayi fiyat listesi</li>
                <li><i class="fa fa-check"></i> Sattığınız her hizmetten kazanç</li>
                <li><i class="fa fa-check"></i> Kendi müşteri paneliniz</li>
                <li><i class="fa fa-check"></i> Teknik desteği biz veriyoruz</li>
            </ul>

            <a href="{{ $_bayiLink }}" class="bt-buton">
                Bayilik Başvurusu Yap <i class="fa fa-arrow-right"></i>
            </a>
            <div class="bt-alt">Başvuru ücretsizdir · 1 iş günü içinde dönüş</div>
        </div>
    </div>
    @endif
</div>

@push('styles')
<style>
    .bayi-tesvik{
        position:relative;overflow:hidden;border-radius:14px;padding:26px 22px 22px;
        background:linear-gradient(160deg,#1a2332 0%,#243040 55%,#1a2332 100%);
        color:#fff;box-shadow:0 10px 30px rgba(26,35,50,.18);
    }
    .bayi-tesvik::after{
        content:"";position:absolute;right:-55px;top:-55px;width:170px;height:170px;
        border-radius:50%;background:rgba(184,182,46,.16);
    }
    .bayi-tesvik .bt-rozet{
        display:inline-block;background:#b8b62e;color:#1a1a0e;font-size:11px;font-weight:800;
        letter-spacing:.08em;padding:5px 11px;border-radius:999px;margin-bottom:14px;
    }
    .bayi-tesvik .bt-ikon{
        position:absolute;right:20px;top:18px;font-size:34px;color:#b8b62e;opacity:.9;z-index:1;
    }
    .bayi-tesvik .bt-baslik{
        font-size:21px;font-weight:800;line-height:1.28;margin:0 0 10px;color:#ffffff !important;
    }
    .bayi-tesvik .bt-metin{
        font-size:13.5px;line-height:1.65;color:#d7dbe4 !important;margin:0 0 16px;
    }
    .bayi-tesvik .bt-metin strong{color:#eceaa0 !important}
    .bayi-tesvik .bt-liste{
        list-style:none;padding:0;margin:0 0 20px;
    }
    .bayi-tesvik .bt-liste li{
        font-size:13.5px;color:#e8eaee !important;padding:6px 0;display:flex;align-items:center;gap:9px;
    }
    .bayi-tesvik .bt-liste i{color:#b8b62e;font-size:12px}
    .bayi-tesvik .bt-buton{
        display:block;text-align:center;background:#b8b62e;color:#1a1a0e !important;
        font-weight:800;font-size:14.5px;padding:13px 18px;border-radius:10px;
        text-decoration:none;transition:.18s;
    }
    .bayi-tesvik .bt-buton:hover{background:#c9c73a;transform:translateY(-1px);text-decoration:none}
    .bayi-tesvik .bt-buton i{margin-left:6px;font-size:12px}
    .bayi-tesvik .bt-alt{
        text-align:center;font-size:11.5px;color:#8b93a5;margin-top:11px;
    }
    /* Geniş ekranda tablolarla aynı hizada dursun, kaydırılınca takip etsin */
    @media (min-width:1200px){
        .bayi-tesvik{position:sticky;top:20px}
    }
    @media (max-width:1199px){
        .bayi-tesvik{margin-top:18px}
    }
</style>
@endpush
@endsection
