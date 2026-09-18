@extends('layouts.panel')

@section('page_title', 'Randevularım')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area">
        <h5 class="title"><i class="fa fa-calendar"></i> {{ __('messages.upcoming_appointments') }}</h5>
    </div>
    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th scope="col" class="text-left">{{ __('messages.date_time') }}</th>
            <th scope="col" class="text-left">Lokasyon</th>
            <th scope="col" class="text-left">{{ __('messages.contact_person') }}</th>
            <th scope="col" class="text-center" style="width:90px">Süre</th>
            <th scope="col" class="text-center" style="width:150px">{{ __('messages.table_status') }}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($gelecek as $r)
            @php
                $bas = \Carbon\Carbon::parse($r->baslangic);
                $sure = !empty($r->bitis) ? $bas->diffInMinutes(\Carbon\Carbon::parse($r->bitis)) : null;
            @endphp
            <tr>
                <th scope="row" class="align-middle">
                    <strong>{{ $bas->format('d.m.Y') }}</strong>
                    <p class="t-detail">{{ $bas->format('H:i') }}</p>
                </th>
                <td class="align-middle">{{ $r->lokasyon_ad ?? '—' }}</td>
                <td class="align-middle">{{ $r->calisan_ad ?? '—' }}</td>
                <td class="text-center align-middle">{{ $sure ? $sure.' dk' : '—' }}</td>
                <td class="text-center align-middle">
                    @if(($r->durum ?? '') == 'onaylandi')
                        <label class="alert alert-info alert-sm mt-3">{{ __('messages.confirmed') }}</label>
                    @else
                        <label class="alert alert-secondary alert-sm mt-3">Beklemede</label>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center p-4">{{ __('messages.no_upcoming_appointments') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if(count($gecmis) > 0)
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area">
        <h5 class="title"><i class="fa fa-history"></i> {{ __('messages.past_appointments') }}</h5>
    </div>
    <table class="table table-bordered table-striped">
        <thead>
        <tr>
            <th scope="col" class="text-left">{{ __('messages.date_time') }}</th>
            <th scope="col" class="text-left">Lokasyon</th>
            <th scope="col" class="text-left">{{ __('messages.contact_person') }}</th>
            <th scope="col" class="text-center" style="width:150px">{{ __('messages.table_status') }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach($gecmis as $r)
            @php $bas = \Carbon\Carbon::parse($r->baslangic); @endphp
            <tr>
                <th scope="row" class="align-middle">
                    <strong>{{ $bas->format('d.m.Y') }}</strong>
                    <p class="t-detail">{{ $bas->format('H:i') }}</p>
                </th>
                <td class="align-middle">{{ $r->lokasyon_ad ?? '—' }}</td>
                <td class="align-middle">{{ $r->calisan_ad ?? '—' }}</td>
                <td class="text-center align-middle">
                    @if(($r->durum ?? '') == 'geldi')
                        <label class="alert alert-success alert-sm mt-3">{{ __('messages.completed') }}</label>
                    @else
                        <label class="alert alert-secondary alert-sm mt-3">Geçmiş</label>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection