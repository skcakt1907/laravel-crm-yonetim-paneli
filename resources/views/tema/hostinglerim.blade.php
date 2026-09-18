@extends('layouts.panel')

@section('page_title', 'Hostinglerim')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-server"></i>
            Hostinglerim
        </h5>
        <a href="{{ Route::has('hosting') ? route('hosting') : url('/hosting') }}" class="btn btn-sm btn-outline-primary pull-right">
            <i class="fa fa-plus"></i> Hosting Al
        </a>
    </div>

    <table id="datatable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th scope="col" class="text-left">{{ __('messages.hosting_detail') }}</th>
                <th scope="col" class="text-center" style="width:120px;">{{ __('messages.package') }}</th>
                <th scope="col" class="text-center" style="width:110px;">{{ __('messages.table_status') }}</th>
                <th scope="col" class="text-center" style="width:140px;">Son Kullanma</th>
                <th scope="col" class="text-center" style="width:120px;">{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($hostingler as $hosting)
            <tr>
                <th scope="row" class="align-middle text-left">
                    <a href="{{ route('uye.hosting.detay', $hosting->id) }}" class="link">{{ $hosting->domain }}</a>
                    <p class="t-detail">{{ $hosting->paket_adi }}</p>
                </th>
                <td class="text-center align-middle">
                    <span class="badge badge-info">{{ $hosting->hosting_kategori->adi ?? 'Hosting' }}</span>
                </td>
                <td class="text-center align-middle">
                    @if($hosting->durum == 1)
                        <label class="alert alert-success alert-sm mt-3">{{ __('messages.active') }}</label>
                    @elseif($hosting->durum == 2)
                        <label class="alert alert-warning alert-sm mt-3">{{ __('messages.suspended') }}</label>
                    @else
                        <label class="alert alert-danger alert-sm mt-3">{{ __('messages.inactive') }}</label>
                    @endif
                </td>
                <td class="text-center align-middle">
                    @if($hosting->bitis_tarihi)
                        <strong>{{ date('d.m.Y', strtotime($hosting->bitis_tarihi)) }}</strong>
                        @php $diff = \Carbon\Carbon::parse($hosting->bitis_tarihi)->diffInDays(now()); @endphp
                        @if($diff <= 30 && $diff >= 0)
                            <p class="t-detail text-danger"><strong>{{ $diff }} gün kaldı</strong></p>
                        @endif
                    @else - @endif
                </td>
                <td class="text-center align-middle">
                    <a href="{{ route('uye.hosting.detay', $hosting->id) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-cog"></i> {{ __('messages.manage') }}
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center p-4">
                    <p>{{ __('messages.no_hosting') }}</p>
                    <a href="{{ route('hosting') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fa fa-search"></i> {{ __('messages.browse_hosting_packages') }}
                    </a>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    @if($hostingler->hasPages())
    <div class="d-flex justify-content-center mt-3">{{ $hostingler->links() }}</div>
    @endif
</div>
@endsection
