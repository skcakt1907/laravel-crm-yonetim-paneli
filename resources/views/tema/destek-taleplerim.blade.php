@extends('layouts.panel')

@section('page_title', 'Destek Taleplerim')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-life-ring"></i>
            Destek Taleplerim
        </h5>
        <a href="{{ route('destek.talebi.olustur') }}" class="btn btn-sm btn-outline-primary pull-right">
            <i class="fa fa-plus"></i> {{ __('messages.create_new_ticket') }}
        </a>
    </div>

    <table id="datatable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th scope="col" class="text-left">Konu</th>
                <th scope="col" class="text-center" style="width:140px;">Kategori</th>
                <th scope="col" class="text-center" style="width:140px;">{{ __('messages.table_status') }}</th>
                <th scope="col" class="text-center" style="width:120px;">{{ __('messages.table_date') }}</th>
                <th scope="col" class="text-center" style="width:120px;">{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($destekler as $destek)
            <tr>
                <th scope="row" class="align-middle text-left">
                    <a href="{{ route('destek.detay', $destek->id) }}" class="link">{{ $destek->baslik }}</a>
                    <p class="t-detail">{{ Str::limit($destek->mesaj, 80) }}</p>
                </th>
                <td class="text-center align-middle">
                    <span class="badge badge-secondary">{{ $destek->hizmet ?? 'Genel' }}</span>
                </td>
                <td class="text-center align-middle">
                    @if($destek->durum == 0)
                        <label class="alert alert-warning alert-sm mt-3">Beklemede</label>
                    @elseif($destek->durum == 1)
                        <label class="alert alert-success alert-sm mt-3">{{ __('messages.status_answered') }}</label>
                    @elseif($destek->durum == 2)
                        <label class="alert alert-info alert-sm mt-3">{{ __('messages.status_customer_reply') }}</label>
                    @else
                        <label class="alert alert-secondary alert-sm mt-3">{{ __('messages.status_closed') }}</label>
                    @endif
                </td>
                <td class="text-center align-middle">
                    <small>
                        {{ date('d.m.Y', strtotime($destek->tarih)) }}<br>
                        {{ date('H:i', strtotime($destek->tarih)) }}
                    </small>
                </td>
                <td class="text-center align-middle">
                    <a href="{{ route('destek.detay', $destek->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-search"></i>
                    </a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center p-4">
                <p>{{ __('messages.empty_support') }}</p>
                <a href="{{ route('destek.talebi.olustur') }}" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-plus"></i> {{ __('messages.create_new_ticket') }}
                </a>
            </td></tr>
        @endforelse
        </tbody>
    </table>

    @if($destekler->hasPages())
    <div class="d-flex justify-content-center mt-3">{{ $destekler->links() }}</div>
    @endif
</div>
@endsection
