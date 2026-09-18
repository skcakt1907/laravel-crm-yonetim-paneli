@extends('layouts.panel')

@section('page_title', 'Favorilerim')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="far fa-heart"></i>
            Favorilerim
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            <a href="{{ route('favorilerim') }}">Favorilerim</a>
        </div>
    </div>

    <table id="datatable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th scope="col" class="text-left">{{ __('messages.product_or_service') }}</th>
                <th scope="col" class="text-center">Fiyat</th>
                <th scope="col" class="text-center" style="width:120px;">{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($favoriler) && (is_countable($favoriler) ? count($favoriler) : 0) > 0)
                @foreach($favoriler as $fav)
                <tr>
                    <th scope="row" class="align-middle text-left">
                        <a href="javascript:void(0)" class="link">{{ $fav->adi ?? $fav->baslik ?? '-' }}</a>
                    </th>
                    <td class="text-center align-middle">{{ isset($fav->fiyat) ? number_format($fav->fiyat,2,',','.').' ₺' : '-' }}</td>
                    <td class="text-center align-middle">
                        <a href="javascript:void(0)" class="btn btn-outline-danger btn-sm" title="{{ __('messages.remove_favorite') }}">
                            <i class="fa fa-heart-broken"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            @else
                <tr><td colspan="3" class="text-center p-4">
                    <i class="far fa-heart" style="font-size:48px;color:#ccc"></i>
                    <p class="mt-3 mb-0">{{ __('messages.no_favorites') }}</p>
                </td></tr>
            @endif
        </tbody>
    </table>
</div>
@endsection
