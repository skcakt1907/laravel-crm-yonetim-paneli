@extends('layouts.panel')

@section('page_title', 'Web Paketlerim')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fab fa-chrome"></i>
            Web Paketlerim
        </h5>
        <a href="{{ Route::has('paketler') ? route('paketler') : url('/paketler') }}" class="btn btn-sm btn-outline-primary pull-right">
            <i class="fa fa-plus"></i> {{ __('messages.buy_new_package') }}
        </a>
    </div>

    <table id="datatable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th scope="col" class="text-left">{{ __('messages.package') }}</th>
                <th scope="col" class="text-left">{{ __('messages.domain') }}</th>
                <th scope="col" class="text-center" style="width:110px;">{{ __('messages.campaign_start') }}</th>
                <th scope="col" class="text-center" style="width:110px;">{{ __('messages.campaign_end') }}</th>
                <th scope="col" class="text-center" style="width:110px;">{{ __('messages.table_status') }}</th>
                <th scope="col" class="text-center" style="width:110px;">{{ __('messages.table_amount') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($paketler as $paket)
            <tr>
                <th scope="row" class="align-middle text-left">
                    <a href="javascript:void(0)" class="link">{{ $paket->paket_adi ?? 'Web Paketi' }}</a>
                    @if($paket->created_at)<p class="t-detail">Satın alma: {{ $paket->created_at->format('d.m.Y H:i') }}</p>@endif
                </th>
                <td class="align-middle">{{ $paket->domain ?? '-' }}</td>
                <td class="text-center align-middle">{{ $paket->baslangic_tarihi ? $paket->baslangic_tarihi->format('d.m.Y') : '-' }}</td>
                <td class="text-center align-middle">{{ $paket->bitis_tarihi ? $paket->bitis_tarihi->format('d.m.Y') : '-' }}</td>
                <td class="text-center align-middle">
                    @switch((int) $paket->durum)
                        @case(1)<label class="alert alert-success alert-sm mt-3">{{ __('messages.active') }}</label>@break
                        @case(2)<label class="alert alert-warning alert-sm mt-3">{{ __('messages.suspended') }}</label>@break
                        @default<label class="alert alert-secondary alert-sm mt-3">{{ __('messages.inactive') }}</label>
                    @endswitch
                </td>
                <td class="text-center align-middle"><strong>{{ number_format($paket->fiyat ?? $paket->tutar ?? 0, 2, ',', '.') }} ₺</strong></td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center p-4">
                    <p>{{ __('messages.no_web_packages') }}</p>
                    <a href="{{ route('paketler') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-search"></i> {{ __('messages.browse_web_packages') }}
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($paketler->hasPages())
    <div class="d-flex justify-content-center mt-3">{{ $paketler->links() }}</div>
    @endif
</div>
@endsection