@extends('layouts.panel')

@section('page_title', 'E-posta Doğrulama')

@section('panel_content')
<div class="col-md-12 main-content">
    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fa fa-envelope"></i>
            {{ __('messages.email_verification') }}
        </h5>
    </div>

    @includeIf('tema.partials.alert-messages')

    <div class="text-center p-5">
        <i class="fa fa-envelope" style="font-size:72px;color:#007bff"></i>
        <h3 class="mt-4">{{ __('messages.verify_your_email') }}</h3>
        <p class="text-muted mt-3">
            {{ __('messages.verification_email_sent') }}<br>
            {{ __('messages.check_inbox_note') }}
        </p>

        <div class="alert alert-info mt-4 text-left">
            <i class="fa fa-info-circle"></i>
            {{ __('messages.resend_verification_note') }}
        </div>

        <form method="POST" action="{{ route('verification.resend') }}" class="mt-4">
            @csrf
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa fa-paper-plane"></i> {{ __('messages.resend_verification_email') }}
            </button>
        </form>

        <div class="mt-4">
            <a href="{{ route('hesabim') }}" class="btn btn-link">
                <i class="fa fa-arrow-left"></i> {{ __('messages.back_to_account') }}
            </a>
        </div>
    </div>
</div>
@endsection
