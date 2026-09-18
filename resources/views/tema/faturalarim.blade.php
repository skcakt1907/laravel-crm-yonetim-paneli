@extends('layouts.panel')

@section('page_title', 'Faturalarım')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-file-invoice"></i>
            {{ __('messages.panel_invoices') }}
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            <a href="{{ route('faturalarim') }}">{{ __('messages.panel_invoices') }}</a>
        </div>
    </div>

    <table id="datatable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th scope="col" class="text-left">{{ __('messages.invoice_detail') }}</th>
                <th scope="col" class="text-center" style="width:120px;">{{ __('messages.table_amount') }}</th>
                <th scope="col" class="text-center" style="width:160px;">{{ __('messages.table_status') }}</th>
                <th scope="col" class="text-center" style="width:180px;">{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($faturalar as $fatura)
            <tr>
                <th scope="row" class="align-middle text-left">
                    <a href="{{ route('fatura.detay', $fatura->id) }}" class="link">Fatura #{{ $fatura->fatura_no }}</a>
                    <p class="t-detail">{{ $fatura->aciklama }}</p>
                    <p class="t-detail">{{ date('d.m.Y H:i', strtotime($fatura->tarih)) }}</p>
                </th>
                <td class="align-middle text-center">
                    @if(!empty($fatura->para_birimi ?? null) && ($fatura->para_birimi ?? 'TL') !== 'TL' && !empty($fatura->doviz_tutar ?? null))
                        <strong>{{ number_format($fatura->doviz_tutar, 2, ',', '.') }} {{ $fatura->para_birimi }}</strong>
                        <p class="t-detail">≈ {{ number_format($fatura->tutar, 2, ',', '.') }} ₺</p>
                    @else
                        <strong>{{ number_format($fatura->tutar, 2, ',', '.') }} ₺</strong>
                    @endif
                </td>
                <td class="align-middle text-center">
                    @if($fatura->durum == 0)
                        <label class="alert alert-danger alert-sm mt-3">{{ __('messages.unpaid') }}</label>
                    @else
                        <label class="alert alert-success alert-sm mt-3">{{ __('messages.paid') }}</label>
                        @if(!empty($fatura->odeme_tarihi))
                            <p class="t-detail">{{ date('d.m.Y', strtotime($fatura->odeme_tarihi)) }}</p>
                        @endif
                    @endif
                </td>
                <td class="align-middle text-center">
                    <a href="{{ route('fatura.detay', $fatura->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-search"></i> {{ __('messages.view') }}
                    </a>
                    @if($fatura->durum == 0)
                        <a href="{{ route('fatura.ode', $fatura->id) }}" class="btn btn-success btn-sm mt-1">
                            <i class="fa fa-credit-card"></i> {{ __('messages.pay') }}
                        </a>
                    @else
                        <a href="{{ route('fatura.indir', $fatura->id) }}" class="btn btn-outline-secondary btn-sm mt-1">
                            <i class="fa fa-download"></i> {{ __('messages.download') }}
                        </a>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center p-4">{{ __('messages.empty_invoices') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($faturalar->hasPages())
    <div class="d-flex justify-content-center mt-3">{{ $faturalar->links() }}</div>
    @endif
</div>
@endsection