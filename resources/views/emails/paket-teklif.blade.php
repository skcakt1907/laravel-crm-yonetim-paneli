{{-- PAKET TEKLİFİ — ortak layout (lime+logo) --}}
@extends('emails.layout', [
    'baslik'  => 'Size Özel Paket Teklifi',
    'ustyazi' => 'Hazırladığımız teklif',
    'ikon'    => '📦',
])

@section('govde')

    <p style="margin:0 0 16px;font-size:16px;font-weight:700;color:#1f2419;">
        Merhaba {{ trim(($uye->ad ?? '') . ' ' . ($uye->soyad ?? '')) ?: 'Değerli müşterimiz' }},
    </p>

    <p style="margin:0 0 22px;">
        Sizin için özel bir paket teklifi hazırladık. Aşağıda seçilen paketleri ve toplam tutarı görebilirsiniz.
    </p>

    {{-- Paket kalemleri --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;border:1px solid #eceee6;border-radius:10px;overflow:hidden;">
        <tr>
            <td style="padding:11px 16px;background-color:#f7f8f3;font-size:12px;font-weight:700;color:#7a8270;text-transform:uppercase;letter-spacing:.5px;">Paket</td>
            <td style="padding:11px 16px;background-color:#f7f8f3;font-size:12px;font-weight:700;color:#7a8270;text-transform:uppercase;letter-spacing:.5px;text-align:center;">Süre</td>
            <td style="padding:11px 16px;background-color:#f7f8f3;font-size:12px;font-weight:700;color:#7a8270;text-transform:uppercase;letter-spacing:.5px;text-align:right;">Tutar (TL)</td>
        </tr>
        @foreach($paketler as $paket)
        @php
            // Hem yeni kalem objesi (adi/birim/ay/satir) hem eski standart paket objesi (adi/tutar) ile uyumlu
            $pAd   = $paket->adi ?? 'Paket';
            $pAy   = $paket->ay ?? 1;
            $pSatir = isset($paket->satir) ? (float) $paket->satir : (float) ($paket->tutar ?? 0);
        @endphp
        <tr>
            <td style="padding:12px 16px;border-top:1px solid #eceee6;font-size:14px;color:#3a4133;">{{ $pAd }}</td>
            <td style="padding:12px 16px;border-top:1px solid #eceee6;font-size:14px;color:#3a4133;text-align:center;">{{ $pAy }} ay</td>
            <td style="padding:12px 16px;border-top:1px solid #eceee6;font-size:14px;color:#3a4133;text-align:right;">{{ number_format($pSatir, 2, ',', '.') }} ₺</td>
        </tr>
        @endforeach
    </table>

    {{-- Toplam --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 24px;">
        <tr>
            <td style="padding:14px 18px;background-color:#f7f8f3;border-radius:10px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td style="font-size:14px;color:#7a8270;">Toplam Tutar</td>
                        <td style="font-size:20px;font-weight:800;color:#6f7320;text-align:right;">{{ number_format($toplamTl, 2, ',', '.') }} ₺</td>
                    </tr>
                    @if($paraBirimi !== 'TL' && $paraBirimi !== 'TRY')
                    <tr>
                        <td style="font-size:13px;color:#9aa08e;padding-top:6px;">Toplam ({{ $paraBirimi }})</td>
                        <td style="font-size:14px;font-weight:700;color:#3a4133;text-align:right;padding-top:6px;">{{ number_format($toplamParaBirimi, 2, ',', '.') }}</td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Buton --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 18px;">
    <tr><td align="center">
        <a href="{{ $detayUrl }}"
           style="display:inline-block;background-color:#b8b62e;color:#1f2419;text-decoration:none;
                  padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;
                  box-shadow:0 6px 16px rgba(184,182,46,0.32);">
            Teklifi Görüntüle →
        </a>
    </td></tr>
    </table>

    <p style="margin:0;font-size:14px;color:#7a8270;">
        Teklifle ilgili sorularınız olursa bu maile yanıt verebilirsiniz.
    </p>

@endsection