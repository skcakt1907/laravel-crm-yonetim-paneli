{{--
    ŞİFRE SIFIRLAMA KODU MAILI
    Kullanim (CustomerNotifier veya ilgili gonderici icinde):
        Mail::send('emails.sifre-sifirlama-kod', [
            'ad'  => $uye->ad,
            'kod' => $kod,          // 6 haneli
        ], function($m) use ($uye) {
            $m->to($uye->email)->subject('Şifre Sıfırlama Kodu');
        });
--}}
@extends('emails.layout', [
    'baslik'  => 'Şifre Sıfırlama',
    'ustyazi' => 'Hesap güvenliği doğrulaması',
    'ikon'    => '🔐',
])

@section('govde')

    <p style="margin:0 0 18px;">
        Merhaba <strong style="color:#6f7320;">{{ $ad ?? 'Değerli kullanıcımız' }}</strong>,
    </p>

    <p style="margin:0 0 24px;">
        Hesabınızın şifresini sıfırlamak için aşağıdaki doğrulama kodunu kullanın:
    </p>

    {{-- KOD KUTUSU --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
    <tr>
        <td align="center" style="background-color:#f7f8f3;border:2px dashed #cfd0b0;border-radius:14px;padding:26px 20px;">
            <div style="font-size:12px;color:#9aa08e;text-transform:uppercase;letter-spacing:2px;margin-bottom:10px;">
                Doğrulama Kodu
            </div>
            <div style="font-size:40px;font-weight:800;color:#6f7320;letter-spacing:10px;font-family:'Courier New',monospace;">
                {{ $kod ?? '------' }}
            </div>
        </td>
    </tr>
    </table>

    {{-- UYARI KUTUSU --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 8px;">
    <tr>
        <td style="background-color:#fff8e6;border-left:4px solid #e0a800;border-radius:8px;padding:14px 18px;">
            <p style="margin:0;font-size:13px;color:#8a6d00;line-height:1.6;">
                <strong>⚠️ Önemli:</strong> Bu kodu kimseyle paylaşmayın. Kod <strong>10 dakika</strong> geçerlidir.
                Eğer bu işlemi siz yapmadıysanız, lütfen hesabınızı kontrol edin.
            </p>
        </td>
    </tr>
    </table>

@endsection