@extends('layouts.master')

@section('title', __('messages.payment'))

@section('content')
<div class="top-header overlay" style="background: linear-gradient(135deg, #1a2332 0%, #2d3e52 100%); padding: 180px 0 80px 0; min-height: 300px;">
    <div class="container">
        <div class="row">
            <div class="col-sm-12 col-md-12">
                <div class="wrapper text-center">
                    <h1 class="heading" style="color: #fff; font-size: 36px; font-weight: 800;">{{ __('messages.payment') }}</h1>
                    <p style="color: rgba(255, 255, 255, 0.7);">{{ __('messages.secure_payment') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div style="background: #0b1120; padding: 60px 0;">
    <div class="container">
        <div class="row">
            <!-- Sipariş Özeti -->
            <div class="col-lg-5 mb-4">
                <div style="background: rgba(30, 40, 60, 0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 30px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7); border: 3px solid rgba(59, 130, 246, 0.5);">
                    <h5 style="color: #fff; font-size: 20px; font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                        <i class="mdi mdi-receipt" style="color: #3b82f6;"></i> {{ __('messages.order_summary') }}
                    </h5>
                    
                    <div style="margin-bottom: 20px;">
                        @foreach($sepet as $item)
                        <div style="background: rgba(20, 30, 50, 0.8); border-radius: 12px; padding: 15px; margin-bottom: 12px; border-left: 3px solid #3b82f6;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
                                <div style="flex: 1;">
                                    <strong style="color: #fff; font-size: 15px; display: block; margin-bottom: 5px;">{{ $item->urun_adi }}</strong>
                                    <p style="color: rgba(255, 255, 255, 0.6); font-size: 12px; margin: 0;">{{ $item->aciklama }}</p>
                                    @if($item->miktar > 1)
                                        <span style="color: rgba(255, 255, 255, 0.5); font-size: 11px; display: inline-block; margin-top: 5px;">{{ $item->miktar }} {{ __('messages.quantity') }}</span>
                                    @endif
                                </div>
                                <div style="text-align: right;">
                                    <strong style="color: #3b82f6; font-size: 16px;">{{ number_format($item->fiyat, 2, ',', '.') }} ₺</strong>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <div style="border-top: 2px solid rgba(255, 255, 255, 0.1); padding-top: 20px; margin-top: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: rgba(255, 255, 255, 0.8); font-size: 18px; font-weight: 600;">{{ __('messages.total') }}:</span>
                            <span style="color: #d4d25b; font-size: 28px; font-weight: 800;">{{ number_format($genel_toplam, 2, ',', '.') }} ₺</span>
                        </div>
                    </div>
                    
                    <a href="{{ route('sepet') }}" style="display: inline-flex; align-items: center; gap: 8px; margin-top: 20px; padding: 10px 20px; background: rgba(107, 114, 128, 0.2); border: 2px solid rgba(107, 114, 128, 0.4); color: #9ca3af; border-radius: 10px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(107, 114, 128, 0.3)'; this.style.borderColor='#9ca3af';" onmouseout="this.style.background='rgba(107, 114, 128, 0.2)'; this.style.borderColor='rgba(107, 114, 128, 0.4)';">
                        <i class="mdi mdi-arrow-left"></i> {{ __('messages.back_to_cart') }}
                    </a>
                </div>
            </div>
            
            <!-- Ödeme Formu -->
            <div class="col-lg-7 mb-4">
                <div style="background: rgba(30, 40, 60, 0.95); backdrop-filter: blur(10px); border-radius: 20px; padding: 30px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7); border: 3px solid rgba(34, 197, 94, 0.5);">
                    <h5 style="color: #fff; font-size: 20px; font-weight: 700; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
                        <i class="mdi mdi-credit-card" style="color: #22c55e;"></i> {{ __('messages.payment_info') }}
                    </h5>
                    
                    @if($paytr_iframe_url)
                        <!-- PayTR iframe -->
                        <div style="background: #fff; border-radius: 12px; overflow: hidden; margin-bottom: 20px;">
                            <script src="https://www.paytr.com/js/iframeResizer.min.js"></script>
                            <iframe src="{{ $paytr_iframe_url }}" id="paytriframe" frameborder="0" scrolling="no" style="width: 100%; min-height: 500px;"></iframe>
                            <script>
                                iFrameResize({}, '#paytriframe');
                            </script>
                        </div>
                        
                        <div style="background: rgba(59, 130, 246, 0.1); border: 2px solid rgba(59, 130, 246, 0.4); border-radius: 12px; padding: 15px; display: flex; align-items: flex-start; gap: 12px;">
                            <i class="mdi mdi-shield-lock" style="color: #3b82f6; font-size: 24px; flex-shrink: 0;"></i>
                            <div>
                                <strong style="color: #fff; display: block; margin-bottom: 5px;">{{ __('messages.secure_payment') }}</strong>
                                <p style="color: rgba(255, 255, 255, 0.7); font-size: 13px; margin: 0;">
                                    {{ __('messages.payment_info_ssl_protected') }}
                                </p>
                            </div>
                        </div>
                    @else
                        <div style="text-align: center; padding: 40px 20px;">
                            <div style="width: 80px; height: 80px; background: rgba(251, 191, 36, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                                <i class="mdi mdi-alert-circle" style="color: #fbbf24; font-size: 40px;"></i>
                            </div>
                            <h6 style="color: #fbbf24; font-size: 18px; font-weight: 700; margin-bottom: 10px;">{{ __('messages.payment_system_unavailable') }}</h6>
                            <p style="color: rgba(255, 255, 255, 0.6); font-size: 14px; margin-bottom: 20px;">
                                {{ __('messages.payment_system_unavailable_desc') }}
                            </p>
                            <a href="{{ route('sepet') }}" style="display: inline-flex; align-items: center; gap: 8px; padding: 12px 25px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; border-radius: 10px; text-decoration: none; font-weight: 600; transition: all 0.3s ease;">
                                <i class="mdi mdi-arrow-left"></i> {{ __('messages.back_to_cart') }}
                            </a>
                        </div>
                    @endif
                </div>
                
                <!-- Güvenlik Bilgisi -->
                <div style="background: rgba(30, 40, 60, 0.95); backdrop-filter: blur(10px); border-radius: 15px; padding: 20px; margin-top: 20px; border: 2px solid rgba(107, 114, 128, 0.3);">
                    <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap; justify-content: center;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            {{-- mdi-shield-check bu font sürümünde yok -> ikon boş çıkıyordu --}}
                            <i class="mdi mdi-shield" style="color: #22c55e;"></i>
                            <span style="color: rgba(255, 255, 255, 0.7); font-size: 13px;">SSL {{ __('messages.protected') }}</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="mdi mdi-lock" style="color: #3b82f6;"></i>
                            <span style="color: rgba(255, 255, 255, 0.7); font-size: 13px;">3D Secure</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="mdi mdi-credit-card-check" style="color: #d4d25b;"></i>
                            <span style="color: rgba(255, 255, 255, 0.7); font-size: 13px;">PayTR</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
