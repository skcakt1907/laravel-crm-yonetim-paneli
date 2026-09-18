@extends('layouts.panel')

@section('page_title', 'Hizmetlerim')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title"><i class="fa fa-briefcase"></i> Hizmetlerim</h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            <span>Hizmetlerim</span>
        </div>
    </div>

    @includeIf('tema.partials.alert-messages')

    <table id="datatable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th class="text-left">{{ __('messages.title') }}</th>
                <th class="text-center" style="width:140px">{{ __('messages.table_amount') }}</th>
                <th class="text-center" style="width:140px">{{ __('messages.table_status') }}</th>
                <th class="text-center" style="width:120px">{{ __('messages.table_date') }}</th>
                <th class="text-center" style="width:100px">{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody>
        @forelse($kayitlar as $k)
            <tr>
                <th class="text-left align-middle">
                    <strong>{{ $k->baslik }}</strong>
                    @if(!empty($k->icerik))
                        <p class="t-detail">{{ Str::limit(strip_tags($k->icerik), 80) }}</p>
                    @endif
                </th>
                <td class="text-center align-middle">{{ $k->tutar ? number_format((float)$k->tutar, 2, ',', '.').' ₺' : '-' }}</td>
                <td class="text-center align-middle">
                    @php $durum = is_numeric($k->durum) ? (int)$k->durum : $k->durum; @endphp
                    @if($durum === 1 || $durum === '1' || $durum === 'aktif')
                        <label class="alert alert-success alert-sm mt-3">{{ __('messages.active') }}</label>
                    @elseif($durum === 0 || $durum === '0' || $durum === 'beklemede')
                        <label class="alert alert-warning alert-sm mt-3">Beklemede</label>
                    @else
                        <label class="alert alert-secondary alert-sm mt-3">{{ $k->durum ?: '-' }}</label>
                    @endif
                </td>
                <td class="text-center align-middle">
                    <small>{{ $k->tarih ? date('d.m.Y', strtotime($k->tarih)) : '-' }}</small>
                </td>
                <td class="text-center align-middle">
                    @if(!empty($k->dosya))
                        <a href="{{ asset('tema/uploads/'.$k->dosya) }}" target="_blank" class="btn btn-outline-primary btn-sm" title="{{ __('messages.download_file') }}">
                            <i class="fa fa-download"></i>
                        </a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center p-4">{{ __('messages.no_services_assigned') }}</td></tr>
        @endforelse
        </tbody>
    </table>

    @if(method_exists($kayitlar, 'hasPages') && $kayitlar->hasPages())
        <div class="d-flex justify-content-center mt-3">{{ $kayitlar->links() }}</div>
    @endif
</div>
@endsection
