@extends('layouts.panel')

@section('page_title', 'Fatura Detayı')

@section('panel_content')
<div class="col-md-12 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-file-invoice"></i>
            Fatura Detayı - #{{ $fatura->fatura_no }}
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            <a href="{{ route('faturalarim') }}">{{ __('messages.panel_invoices') }}</a> /
            #{{ $fatura->fatura_no }}
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <div class="alert {{ $fatura->durum == 0 ? 'alert-danger' : 'alert-success' }}">
                <strong>{{ __('messages.payment_status_label') }}</strong>
                {{ $fatura->durum == 0 ? 'Ödenmedi' : 'Ödendi' }}
            </div>
        </div>
        <div class="col-md-4">
            @php
                $fDovizli = !empty($fatura->para_birimi ?? null) && ($fatura->para_birimi ?? 'TL') !== 'TL' && !empty($fatura->doviz_tutar ?? null);
            @endphp
            <div class="alert alert-info">
                @if($fDovizli)
                    <strong>{{ __('messages.amount_label') }}</strong> {{ number_format($fatura->doviz_tutar, 2, ',', '.') }} {{ $fatura->para_birimi }}
                    <small style="display:block;margin-top:2px">TCMB kuru {{ number_format($fatura->kur ?? 0, 4, ',', '.') }} ≈ {{ number_format($fatura->tutar, 2, ',', '.') }} ₺</small>
                @else
                    <strong>{{ __('messages.amount_label') }}</strong> {{ number_format($fatura->tutar, 2, ',', '.') }} ₺
                @endif
            </div>
        </div>
        @if($fatura->durum == 1 && $fatura->odeme_tarihi)
        <div class="col-md-4">
            <div class="alert alert-secondary">
                <strong>{{ __('messages.payment_date_label') }}</strong> {{ \Carbon\Carbon::parse($fatura->odeme_tarihi)->format('d.m.Y H:i') }}
            </div>
        </div>
        @endif
    </div>

    <table class="table table-bordered table-striped">
        <tbody>
            <tr>
                <th scope="row" style="width:200px;">{{ __('messages.invoice_no') }}</th>
                <td>{{ $fatura->fatura_no }}</td>
            </tr>
            <tr>
                <th scope="row">{{ __('messages.invoice_date') }}</th>
                <td>{{ \Carbon\Carbon::parse($fatura->tarih)->format('d.m.Y H:i') }}</td>
            </tr>
            @if($fatura->vadeTarihi ?? $fatura->vade_tarihi ?? null)
            <tr>
                <th scope="row">Vade Tarihi</th>
                <td>{{ \Carbon\Carbon::parse($fatura->vadeTarihi ?? $fatura->vade_tarihi)->format('d.m.Y') }}</td>
            </tr>
            @endif
            @if($fatura->aciklama)
            <tr>
                <th scope="row">{{ __('messages.description') }}</th>
                <td>{{ $fatura->aciklama }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    @if($fatura->kalemler ?? null)
    <h5 class="mt-4 mb-3"><i class="fa fa-list"></i> {{ __('messages.invoice_items') }}</h5>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th class="text-left">{{ __('messages.product_or_service') }}</th>
                <th class="text-center" style="width:100px;">{{ __('messages.count_unit') }}</th>
                <th class="text-right" style="width:140px;">Birim Fiyat</th>
                <th class="text-right" style="width:140px;">{{ __('messages.total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fatura->kalemler as $kalem)
            <tr>
                <td>{{ $kalem->ad ?? $kalem->adi }}</td>
                <td class="text-center">{{ $kalem->miktar ?? 1 }}</td>
                <td class="text-right">{{ number_format($kalem->birim_fiyat ?? $kalem->fiyat, 2, ',', '.') }} ₺</td>
                <td class="text-right"><strong>{{ number_format(($kalem->birim_fiyat ?? $kalem->fiyat) * ($kalem->miktar ?? 1), 2, ',', '.') }} ₺</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">{{ __('messages.grand_total') }}</th>
                <th class="text-right">{{ number_format($fatura->tutar, 2, ',', '.') }} ₺</th>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="text-center mt-4 mb-3">
        @if($fatura->durum == 0)
            <a href="{{ route('fatura.ode', $fatura->id) }}" class="btn btn-success">
                <i class="fa fa-credit-card"></i> {{ __('messages.pay_immediately') }}
            </a>
        @else
            <a href="{{ route('fatura.indir', $fatura->id) }}" class="btn btn-outline-primary">
                <i class="fa fa-download"></i> {{ __('messages.download_pdf') }}
            </a>
        @endif
        <a href="{{ route('faturalarim') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> {{ __('messages.go_back') }}
        </a>
    </div>

    @if($fatura->durum == 0)
    <div class="alert alert-warning text-center mt-3">
        <i class="fa fa-exclamation-triangle"></i>
        <strong>{{ __('messages.invoice_unpaid_notice') }}</strong> {{ __('messages.pay_asap_note') }}
    </div>
    @endif
</div>
@endsection