@extends('layouts.master')

@section('title', 'PayTR Ödeme')

@section('content')
<div class="top-header overlay" style="background-image: url({{ asset('tema/uploads/arkaplan/uyelik/bg.jpg') }})">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper">
                    <h1 class="heading">{{ __('messages.secure_payment') }}</h1>
                    <h3 class="subheading">{{ __('messages.complete_payment_paytr') }}</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mixcontainer">
    <div class="container">
        <div id="wrapper" class="mt-4 mb-4">
            <div class="row">
                <div class="col-md-12">
                    <div class="border-left-3 main-content">
                        <div class="title-area mb-4">
                            <h5 class="title"><i class="mdi mdi-credit-card"></i> {{ __('messages.payment_form') }}</h5>
                        </div>

                        <div style="width: 100%; margin: 0 auto; display: table;">
                            <!-- PayTR iframe -->
                            <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
                            <iframe src="{{ $iframe_url }}" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%; min-height: 600px;"></iframe>
                            <script>
                                iFrameResize({}, '#paytriframe');
                            </script>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="mdi mdi-loading"></i> 
                            <strong>{{ __('messages.secure_payment_label') }}</strong> Ödeme bilgileriniz PayTR tarafından güvenli bir şekilde işlenmektedir. 
                            Kart bilgileriniz sistemimizde saklanmamaktadır.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

