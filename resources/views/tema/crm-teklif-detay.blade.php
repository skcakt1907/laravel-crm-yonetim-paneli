@extends('layouts.panel')

@section('page_title', 'Özel Teklif Detayı')

@section('panel_content')
<div class="col-md-12 border-left-3 main-content">

    <div class="title-area mb-4">
        <h5 class="title">
            <i class="fas fa-file-invoice-dollar"></i>
            {{ __('messages.offer_detail') }}
        </h5>
        <a href="{{ route('uye.crm.tekliflerim') }}" class="btn btn-sm btn-outline-secondary pull-right">
            <i class="fa fa-arrow-left"></i> {{ __('messages.all_my_special_offers') }}
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{!! session('success') !!}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{!! session('error') !!}</div>
    @endif

    <div style="background:#fff;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,.06);overflow:hidden;border:1px solid #e5e7eb;">

        {{-- Durum şeridi (üstte) --}}
        @php $d = $talep->durum ?? 'bekliyor'; @endphp
        @if($d === 'odendi')
            <div style="padding:14px 20px;background:#fef3c7;color:#92400e;text-align:center;font-weight:600;font-size:14px;">
                {{ __('messages.payment_notice_received') }}
            </div>
        @elseif(in_array($d, ['onaylandi','paid']))
            <div style="padding:14px 20px;background:#d1fae5;color:#065f46;text-align:center;font-weight:600;font-size:14px;">
                {{ __('messages.payment_approved_thanks') }}
            </div>
        @elseif(in_array($d, ['reddedildi','iptal']))
            <div style="padding:14px 20px;background:#fee2e2;color:#991b1b;text-align:center;font-weight:600;font-size:14px;">
                ✕ Bu teklif {{ $d === 'iptal' ? 'iptal edildi' : 'reddedildi' }}.
            </div>
        @elseif($d === 'kabul_edildi')
            <div style="padding:14px 20px;background:#d1fae5;color:#065f46;text-align:center;font-weight:600;font-size:14px;">
                {{ __('messages.offer_accepted_proceed') }}
            </div>
        @endif

        {{-- Header (paket başlığı + tutar) --}}
        @php
            $isBayi = ($talep->tip ?? '') === 'bayi';
            $gradientStart = $isBayi ? '#f59e0b' : '#8b5cf6';
            $gradientEnd   = $isBayi ? '#d97706' : '#6d28d9';
        @endphp
        <div style="background:linear-gradient(135deg,{{ $gradientStart }},{{ $gradientEnd }});color:#fff;padding:36px 28px;text-align:center;">
            <div style="font-size:48px;line-height:1;margin-bottom:6px;">
                {{ $isBayi ? '⭐' : '🎁' }}
            </div>
            <div style="font-size:13px;opacity:.9;text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">
                {{ $isBayi ? 'Bayilik Teklifi' : 'Size Özel Teklif' }}
            </div>
            <h2 style="font-size:22px;font-weight:700;margin:0 0 14px;">{{ $talep->paket_adi }}</h2>
            <div style="font-size:36px;font-weight:800;">
                ₺{{ number_format((float)$talep->tutar, 2, ',', '.') }}
            </div>
        </div>

        {{-- Body --}}
        <div style="padding:28px;">

            {{-- Bilgi satırları --}}
            <div style="background:#f8fafc;padding:16px 18px;border-radius:12px;margin-bottom:20px;font-size:14px;">
                <div style="padding:6px 0;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">{{ __('messages.payment_method') }}</span>
                    <strong style="color:#1e293b;">
                        {{ ($talep->odeme_yontemi ?? '') === 'online' ? '💳 Online Ödeme' : '🏦 Havale / EFT' }}
                    </strong>
                </div>
                <div style="padding:6px 0;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">{{ __('messages.offer_date') }}</span>
                    <strong style="color:#1e293b;">
                        {{ \Carbon\Carbon::parse($talep->sent_at ?? $talep->created_at)->format('d.m.Y H:i') }}
                    </strong>
                </div>
                <div style="padding:6px 0;display:flex;justify-content:space-between;">
                    <span style="color:#64748b;">{{ __('messages.offer_no') }}</span>
                    <strong style="color:#1e293b;">#{{ $talep->id }}</strong>
                </div>
            </div>

            {{-- Mesaj --}}
            @if(!empty($talep->mesaj))
                <div style="background:#eef2ff;border-left:4px solid #28364d;padding:14px 16px;border-radius:8px;margin-bottom:20px;font-size:14px;color:#3730a3;line-height:1.6;">
                    <strong style="display:block;margin-bottom:6px;">{{ __('messages.our_message') }}</strong>
                    {!! nl2br(e($talep->mesaj)) !!}
                </div>
            @endif

            {{-- AKSİYON BUTONLARI --}}
            @if(in_array($d, ['gonderildi','bekliyor']))
                {{-- 1. AŞAMA: Henüz kabul/reddetmemiş --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px;">
                    <form method="POST" action="{{ route('uye.crm.teklif.kabul', $talep->id) }}" onsubmit="return confirm('Bu teklifi kabul etmek istediğinize emin misiniz?');">
                        @csrf
                        <button type="submit" style="width:100%;background:linear-gradient(135deg,#10b981,#059669);color:#fff;border:none;padding:14px;font-size:14px;font-weight:700;border-radius:10px;cursor:pointer;">
                            {{ __('messages.accept_offer_button') }}
                        </button>
                    </form>
                    <button type="button" onclick="document.getElementById('panelRedModal').style.display='flex'" style="width:100%;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;border:none;padding:14px;font-size:14px;font-weight:700;border-radius:10px;cursor:pointer;">
                        ✕ REDDET
                    </button>
                </div>

                <div style="text-align:center;color:#94a3b8;font-size:12px;margin:12px 0;">{{ __('messages.or_directly') }}</div>

                <form method="POST" action="{{ route('uye.crm.teklif.odedim', $talep->id) }}" onsubmit="return confirm('Ödeme yaptığınızı bildirmek istiyor musunuz? Ödemeniz manuel olarak onaylanacaktır.');">
                    @csrf
                    <button type="submit" style="width:100%;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;padding:16px;font-size:15px;font-weight:700;border-radius:10px;cursor:pointer;">
                        @if(($talep->odeme_yontemi ?? '') === 'online')
                            💳 ÖDEMEYİ TAMAMLA
                        @else
                            ✓ ÖDEMEYİ YAPTIM (BİLDİR)
                        @endif
                    </button>
                </form>

                @if(($talep->odeme_yontemi ?? '') === 'havale')
                    <p style="margin-top:14px;font-size:12px;color:#64748b;text-align:center;line-height:1.6;">
                        {{ __('messages.for_bank_details') }} <strong>{{ $ayarlar->firma_email ?? 'bizimle iletişim' }}</strong> kurabilirsiniz.<br>
                        {{ __('messages.after_payment_press_button') }}
                    </p>
                @endif

            @elseif($d === 'kabul_edildi')
                {{-- 2. AŞAMA: Kabul etti, ödeme bekleniyor --}}
                <form method="POST" action="{{ route('uye.crm.teklif.odedim', $talep->id) }}" onsubmit="return confirm('Ödeme yaptığınızı bildirmek istiyor musunuz?');">
                    @csrf
                    <button type="submit" style="width:100%;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;padding:16px;font-size:15px;font-weight:700;border-radius:10px;cursor:pointer;">
                        @if(($talep->odeme_yontemi ?? '') === 'online')
                            💳 ÖDEMEYİ TAMAMLA
                        @else
                            ✓ ÖDEMEYİ YAPTIM (BİLDİR)
                        @endif
                    </button>
                </form>

                @if(($talep->odeme_yontemi ?? '') === 'havale')
                    <p style="margin-top:14px;font-size:12px;color:#64748b;text-align:center;line-height:1.6;">
                        {{ __('messages.for_bank_details') }} <strong>{{ $ayarlar->firma_email ?? 'bizimle iletişim' }}</strong> kurabilirsiniz.
                    </p>
                @endif
            @endif
        </div>
    </div>

    {{-- RED MODAL --}}
    @if(in_array($d, ['gonderildi','bekliyor']))
    <div id="panelRedModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,.6);z-index:9999;align-items:center;justify-content:center;padding:20px;">
        <div style="background:#fff;border-radius:16px;max-width:480px;width:100%;padding:28px;">
            <h3 style="font-size:18px;font-weight:700;color:#1e293b;margin-bottom:8px;">Teklifi Reddet</h3>
            <p style="font-size:14px;color:#64748b;margin-bottom:16px;">Reddetme sebebinizi belirtmek ister misiniz? (Opsiyonel)</p>
            <form method="POST" action="{{ route('uye.crm.teklif.red', $talep->id) }}">
                @csrf
                <textarea name="red_sebep" rows="4" placeholder="{{ __('messages.reject_reason_placeholder') }}" style="width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:12px 14px;font-size:14px;resize:vertical;font-family:inherit;"></textarea>
                <div style="display:flex;gap:10px;margin-top:14px;">
                    <button type="button" onclick="document.getElementById('panelRedModal').style.display='none'" style="flex:1;padding:12px;border:1px solid #e2e8f0;background:#fff;color:#64748b;border-radius:10px;font-weight:600;cursor:pointer;">{{ __('messages.give_up') }}</button>
                    <button type="submit" style="flex:1;padding:12px;border:none;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;border-radius:10px;font-weight:700;cursor:pointer;">Reddet</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection