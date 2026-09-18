@extends('layouts.panel')

@section('page_title', 'Fatura Ödeme')

@section('panel_content')
@php
    $bakiye = Auth::guard('uye')->user()->bakiye ?? 0;
    $yeterli_bakiye = $bakiye >= $fatura->tutar;
@endphp

<div class="col-md-12 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-credit-card"></i>
            Fatura Ödeme - #{{ $fatura->fatura_no }}
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            <a href="{{ route('faturalarim') }}">{{ __('messages.panel_invoices') }}</a> /
            <a href="{{ route('fatura.detay', $fatura->id) }}">#{{ $fatura->fatura_no }}</a> /
            Ödeme
        </div>
    </div>

    @includeIf('tema.partials.alert-messages')

    <div class="row">
        {{-- Fatura Özeti --}}
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading"><strong><i class="fa fa-file-text"></i> {{ __('messages.invoice_summary') }}</strong></div>
                <div class="panel-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr><th style="width:160px;">{{ __('messages.invoice_no') }}</th><td>{{ $fatura->fatura_no }}</td></tr>
                            <tr><th>{{ __('messages.invoice_date') }}</th><td>{{ \Carbon\Carbon::parse($fatura->tarih)->format('d.m.Y') }}</td></tr>
                            @if($fatura->aciklama)
                            <tr><th>{{ __('messages.description') }}</th><td>{{ $fatura->aciklama }}</td></tr>
                            @endif
                        </tbody>
                    </table>
                    <div class="alert alert-success text-center mb-0">
                        <small>{{ __('messages.amount_due') }}</small>
                        <h3 class="mb-0"><strong>{{ number_format($fatura->tutar, 2, ',', '.') }} ₺</strong></h3>
                    </div>
                </div>
            </div>
        </div>

        {{-- Ödeme Yöntemi --}}
        <div class="col-md-6">
            <div class="panel panel-default">
                <div class="panel-heading"><strong><i class="fa fa-credit-card"></i> {{ __('messages.payment_method') }}</strong></div>
                <div class="panel-body">
                    <form action="{{ route('fatura.ode.baslat', $fatura->id) }}" method="POST">
                        @csrf

                        <div class="custom-control custom-radio mb-3">
                            <input type="radio" id="odeme_kk" name="odeme_yontemi" value="kredi_karti" class="custom-control-input" checked>
                            <label class="custom-control-label" for="odeme_kk">
                                <strong><i class="fa fa-credit-card"></i> {{ __('messages.credit_debit_card') }}</strong>
                                <br><small>Visa, Mastercard, Troy</small>
                            </label>
                        </div>

                        <div class="custom-control custom-radio mb-3">
                            <input type="radio" id="odeme_bakiye" name="odeme_yontemi" value="bakiye" class="custom-control-input" {{ !$yeterli_bakiye ? 'disabled' : '' }}>
                            <label class="custom-control-label" for="odeme_bakiye">
                                <strong><i class="fa fa-wallet"></i> {{ __('messages.pay_with_balance_button') }}</strong>
                                <br><small>Mevcut bakiye: {{ number_format($bakiye, 2, ',', '.') }} ₺</small>
                                @if(!$yeterli_bakiye)<span class="badge badge-danger ml-2">Yetersiz</span>@endif
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success btn-block btn-lg mt-3">
                            <i class="fa fa-lock"></i> {{ __('messages.make_secure_payment') }}
                        </button>
                    </form>

                    <div class="alert alert-info mt-3 mb-0">
                        <small><i class="fa fa-shield-alt"></i> {{ __('messages.payment_ssl_note') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="{{ route('fatura.detay', $fatura->id) }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> {{ __('messages.go_back') }}
        </a>
    </div>
</div>
@endsection
