{{--
    ŞİFRE DEĞİŞTİ — GÜVENLİK BİLDİRİMİ
    Kullanim:
        Mail::send('emails.sifre-degisti', [
            'ad'    => $uye->ad,
            'ip'    => $ip,            // opsiyonel
            'tarih' => now()->format('d.m.Y H:i'),  // opsiyonel
        ], function($m) use ($uye) {
            $m->to($uye->email)->subject('Şifreniz Değiştirildi');
        });
--}}
@extends('emails.layout', [
    'baslik'  => 'Şifreniz Değiştirildi',
    'ustyazi' => 'Hesap güvenlik bildirimi',
    'ikon'    => '🔒',
])

@section('govde')

    <p style="margin:0 0 18px;">
        Merhaba <strong style="color:#6f7320;">{{ $ad ?? 'Değerli kullanıcımız' }}</strong>,
    </p>

    <p style="margin:0 0 22px;">
        Hesabınızın şifresi başarıyla değiştirildi. İşlem detayları aşağıdadır:
    </p>

    {{-- BİLGİ SATIRLARI (kalip: detay listesi) --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px;border:1px solid #eceee6;border-radius:10px;overflow:hidden;">
        <tr>
            <td style="padding:12px 18px;background-color:#f7f8f3;font-size:13px;color:#7a8270;width:40%;border-bottom:1px solid #eceee6;">Tarih</td>
            <td style="padding:12px 18px;font-size:14px;color:#3a4133;font-weight:600;border-bottom:1px solid #eceee6;">{{ $tarih ?? now()->format('d.m.Y H:i') }}</td>
        </tr>
        @if(!empty($ip))
        <tr>
            <td style="padding:12px 18px;background-color:#f7f8f3;font-size:13px;color:#7a8270;">IP Adresi</td>
            <td style="padding:12px 18px;font-size:14px;color:#3a4133;font-weight:600;">{{ $ip }}</td>
        </tr>
        @endif
    </table>

    {{-- UYARI --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td style="background-color:#fdecec;border-left:4px solid #e05252;border-radius:8px;padding:14px 18px;">
            <p style="margin:0;font-size:13px;color:#9a2e2e;line-height:1.6;">
                <strong>Bu işlemi siz yapmadıysanız</strong> hesabınız risk altında olabilir.
                Lütfen hemen yeni bir şifre belirleyin ve destek ekibimizle iletişime geçin.
            </p>
        </td>
    </tr>
    </table>

@endsection