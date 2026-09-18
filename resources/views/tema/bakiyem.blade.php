@extends('layouts.panel')

@section('page_title', 'Bakiyem')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-credit-card"></i>
            Bakiyem
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            Bakiyem
        </div>
    </div>

    @includeIf('tema.partials.alert-messages')

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="alert alert-primary text-center">
                <h6><i class="fa fa-wallet"></i> {{ __('messages.current_balance') }}</h6>
                <h3 class="mb-0"><strong>{{ number_format($bakiye ?? 0, 2, ',', '.') }} ₺</strong></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-success text-center">
                <h6><i class="fa fa-arrow-down"></i> {{ __('messages.total_deposit') }}</h6>
                <h3 class="mb-0"><strong>{{ number_format($toplam_yukleme ?? 0, 2, ',', '.') }} ₺</strong></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-danger text-center">
                <h6><i class="fa fa-arrow-up"></i> {{ __('messages.total_spending') }}</h6>
                <h3 class="mb-0"><strong>{{ number_format($toplam_harcama ?? 0, 2, ',', '.') }} ₺</strong></h3>
            </div>
        </div>
    </div>

    <div class="panel panel-default mb-4">
        <div class="panel-heading"><strong><i class="fa fa-credit-card"></i> {{ __('messages.add_balance') }}</strong></div>
        <div class="panel-body">
            <form action="{{ route('bakiye.yukle') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    @foreach([50, 100, 250, 500, 1000, 2500] as $i => $tutar)
                    <div class="col-md-2 col-sm-4 col-6 mb-2">
                        <label class="d-block">
                            <input type="radio" name="tutar" value="{{ $tutar }}" {{ $loop->first ? 'checked' : '' }} class="d-none">
                            <div class="alert alert-secondary text-center mb-0 tutar-secim" style="cursor:pointer">
                                <strong>{{ number_format($tutar, 0, ',', '.') }} ₺</strong>
                            </div>
                        </label>
                    </div>
                    @endforeach
                </div>

                <div class="row align-items-end">
                    <div class="form-group col-md-8">
                        <label for="ozel_tutar">{{ __('messages.or_enter_custom_amount') }}</label>
                        <input type="number" id="ozel_tutar" name="ozel_tutar" class="form-control" min="10" max="10000" step="1" placeholder="{{ __('messages.eg_750') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fa fa-credit-card"></i> {{ __('messages.add_balance') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading"><strong><i class="fa fa-history"></i> {{ __('messages.balance_history') }}</strong></div>
        <div class="panel-body">
            @if(isset($bakiye_gecmisi) && $bakiye_gecmisi->count() > 0)
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th class="text-left">{{ __('messages.action') }}</th>
                        <th class="text-center" style="width:140px;">{{ __('messages.table_amount') }}</th>
                        <th class="text-center" style="width:140px;">{{ __('messages.balance') }}</th>
                        <th class="text-center" style="width:160px;">{{ __('messages.table_date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bakiye_gecmisi as $islem)
                    <tr>
                        <td>
                            @if($islem->tip === 'yukleme')
                                <span class="badge badge-success"><i class="fa fa-arrow-down"></i> Yükleme</span>
                            @else
                                <span class="badge badge-danger"><i class="fa fa-arrow-up"></i> Harcama</span>
                            @endif
                            @if($islem->aciklama)
                                <p class="t-detail mt-1 mb-0">{{ $islem->aciklama }}</p>
                            @endif
                        </td>
                        <td class="text-center {{ $islem->tip === 'yukleme' ? 'text-success' : 'text-danger' }}">
                            <strong>{{ $islem->tip === 'yukleme' ? '+' : '-' }}{{ number_format($islem->tutar, 2, ',', '.') }} ₺</strong>
                        </td>
                        <td class="text-center">{{ number_format($islem->bakiye_sonra, 2, ',', '.') }} ₺</td>
                        <td class="text-center"><small>{{ \Carbon\Carbon::parse($islem->tarih)->format('d.m.Y H:i') }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($bakiye_gecmisi->hasPages())
                <div class="d-flex justify-content-center">{{ $bakiye_gecmisi->links() }}</div>
            @endif
            @else
            <div class="text-center p-4 text-muted">{{ __('messages.no_balance_transactions') }}</div>
            @endif
        </div>
    </div>
</div>

@push('styles')
<style>
.tutar-secim:hover{background:#e9ecef!important;border-color:#adb5bd!important}
input[type=radio]:checked + .tutar-secim{background:#28a745!important;color:#fff!important;border-color:#28a745!important}
</style>
@endpush
@endsection
