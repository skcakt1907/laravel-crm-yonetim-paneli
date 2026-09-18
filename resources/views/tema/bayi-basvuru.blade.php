@extends('layouts.panel')

@section('page_title', 'Bayi Başvurusu')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-handshake"></i>
            {{ __('messages.reseller_application') }}
        </h5>
        <div class="pull-right">
            <strong><a href="{{ route('hesabim') }}">{{ __('messages.my_account') }}</a></strong> /
            Bayi Başvurusu
        </div>
    </div>

    @includeIf('tema.partials.alert-messages')

    @if(isset($beklemede) && $beklemede)
        <div class="alert alert-info text-center p-5">
            <i class="fa fa-clock" style="font-size:64px"></i>
            <h4 class="mt-3">{{ __('messages.application_under_review') }}</h4>
            <p class="mb-0">{{ __('messages.application_review_note') }}</p>
        </div>
    @else
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="alert alert-success text-center">
                    <i class="fa fa-money-bill-wave fa-2x"></i>
                    <h6 class="mt-2">{{ __('messages.commission_earning') }}</h6>
                    <small>{{ __('messages.commission_per_sale') }}</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-info text-center">
                    <i class="fa fa-users fa-2x"></i>
                    <h6 class="mt-2">{{ __('messages.reseller_customer_management') }}</h6>
                    <small>{{ __('messages.manage_own_customers') }}</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-warning text-center">
                    <i class="fa fa-chart-line fa-2x"></i>
                    <h6 class="mt-2">{{ __('messages.dedicated_panel') }}</h6>
                    <small>{{ __('messages.reseller_panel_desc') }}</small>
                </div>
            </div>
        </div>

        <form action="{{ route('bayi.basvuru.post') }}" method="POST">
            @csrf
            <div class="panel panel-default">
                <div class="panel-heading"><strong><i class="fa fa-edit"></i> {{ __('messages.application_info') }}</strong></div>
                <div class="panel-body">
                    <div class="form-group">
                        <label for="firma_adi">{{ __('messages.company_name') }} <span class="text-danger">*</span></label>
                        <input type="text" id="firma_adi" name="firma_adi" class="form-control" value="{{ old('firma_adi', $uye->firmaadi ?? '') }}" placeholder="{{ __('messages.company_name_placeholder') }}" required>
                        @error('firma_adi')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="telefon">Telefon <span class="text-danger">*</span></label>
                        <input type="text" id="telefon" name="telefon" class="form-control" value="{{ old('telefon', $uye->telefon ?? '') }}" placeholder="05xx xxx xx xx" required>
                        @error('telefon')<div class="text-danger">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label for="adres">{{ __('messages.address') }}</label>
                        <input type="text" id="adres" name="adres" class="form-control" value="{{ old('adres', $uye->adres ?? '') }}" placeholder="{{ __('messages.business_address_placeholder') }}">
                    </div>
                    <div class="form-group">
                        <label for="neden">Neden bayi olmak istiyorsunuz?</label>
                        <textarea name="neden" id="neden" class="form-control" rows="4" placeholder="{{ __('messages.application_note_placeholder') }}">{{ old('neden') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <a href="{{ route('hesabim') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-times"></i> {{ __('messages.cancel') }}
                </a>
                <button type="submit" class="btn btn-primary pull-right">
                    <i class="fa fa-paper-plane"></i> {{ __('messages.submit_application') }}
                </button>
                <div class="clear"></div>
            </div>
        </form>
    @endif
</div>
@endsection
