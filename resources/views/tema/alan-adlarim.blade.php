@extends('layouts.panel')

@section('page_title', 'Alan Adlarım')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-globe"></i>
            {{ __('messages.panel_domains') }}
        </h5>
        <a href="{{ Route::has('domain.tescil') ? route('domain.tescil') : url('/domain-tescil') }}" class="btn btn-sm btn-outline-primary pull-right">
            <i class="fa fa-plus"></i> Domain Al
        </a>
    </div>

    @if($tum_domainler && $tum_domainler->count() > 0)
    <table id="datatable" class="table table-bordered table-striped">
        <thead>
            <tr>
                <th scope="col" class="text-left">Domain</th>
                <th scope="col" class="text-center" style="width:120px;">{{ __('messages.registration_date') }}</th>
                <th scope="col" class="text-center" style="width:120px;">{{ __('messages.expiry_date') }}</th>
                <th scope="col" class="text-center" style="width:120px;">{{ __('messages.table_status') }}</th>
                <th scope="col" class="text-center" style="width:140px;">Order ID</th>
                <th scope="col" class="text-center" style="width:80px;">{{ __('messages.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tum_domainler as $domain)
            <tr>
                <th scope="row" class="align-middle text-left">
                    <a href="javascript:void(0)" class="link">{{ $domain->domain }}</a>
                    @if(isset($domain->tip) && $domain->tip === 'eski')
                        <p class="t-detail">{{ __('messages.legacy_system') }}</p>
                    @endif
                </th>
                <td class="text-center align-middle">
                    @if($domain->kayit_tarihi){{ is_string($domain->kayit_tarihi) ? \Carbon\Carbon::parse($domain->kayit_tarihi)->format('d.m.Y') : $domain->kayit_tarihi->format('d.m.Y') }}@else-@endif
                </td>
                <td class="text-center align-middle">
                    @if($domain->bitis_tarihi){{ is_string($domain->bitis_tarihi) ? \Carbon\Carbon::parse($domain->bitis_tarihi)->format('d.m.Y') : $domain->bitis_tarihi->format('d.m.Y') }}@else-@endif
                </td>
                <td class="text-center align-middle">
                    @if($domain->durum == 1 || $domain->durum === 'active')
                        <label class="alert alert-success alert-sm mt-3">{{ __('messages.active') }}</label>
                    @elseif($domain->durum === 'paid')
                        <label class="alert alert-info alert-sm mt-3">{{ __('messages.paid') }}</label>
                    @else
                        <label class="alert alert-secondary alert-sm mt-3">{{ __('messages.inactive') }}</label>
                    @endif
                </td>
                <td class="text-center align-middle"><small>{{ $domain->order_id ?? '-' }}</small></td>
                <td class="text-center align-middle">
                    @if(isset($domain->tip) && $domain->tip === 'yeni')
                        <a href="{{ route('domain.status', $domain->id) }}" class="btn btn-outline-primary btn-sm" title="{{ __('messages.check_status') }}">
                            <i class="fa fa-sync"></i>
                        </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="alert alert-warning alert-dismissible fade show mt-4" role="alert">
        <button type="button" class="close mt-0" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
        {{ __('messages.domains_support_hint') }}
    </div>
    @else
    <div class="text-center p-5">
        <i class="fa fa-globe" style="font-size:64px;color:#ccc"></i>
        <h5 class="mt-3">{{ __('messages.no_domains_yet') }}</h5>
        <p class="text-muted">{{ __('messages.buy_domain_hint') }}</p>
        <a href="{{ route('domain.tescil') }}" class="btn btn-outline-primary">
            <i class="fa fa-search"></i> {{ __('messages.check_domain') }}
        </a>
    </div>
    @endif
</div>
@endsection
