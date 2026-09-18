{{--
    ORTAK MAIL LAYOUT — İş Ortağım / DN Kreatif
    --------------------------------------------------
    Kullanim: her mail bunu genisletir ve sadece kendi icerigini yazar:

        @extends('emails.layout', [
            'baslik'   => 'Sifre Sifirlama',
            'ustyazi'  => 'Hesap guvenligi',
            'ikon'     => '🔐',
        ])
        @section('govde')
            ... mail icerigi ...
        @endsection

    Degiskenler (hepsi opsiyonel, makul varsayilanlari var):
      $baslik   : header'daki buyuk baslik
      $ustyazi  : header'da baslik altindaki kucuk yazi
      $ikon     : header'daki emoji ikon (varsayilan yok)
    Mail istemcileri <style> blogunu cogunlukla atar; bu yuzden TUM stiller inline.
--}}
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>{{ $baslik ?? 'İş Ortağım' }}</title>
</head>
<body style="margin:0;padding:0;background-color:#eef0e8;font-family:'Segoe UI',Helvetica,Arial,sans-serif;color:#1f2419;-webkit-font-smoothing:antialiased;">

{{-- On izleme metni (gelen kutusunda konu yaninda gorunur, mailde gizli) --}}
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">
    {{ $onizleme ?? ($ustyazi ?? 'İş Ortağım bildirimi') }}
</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef0e8;padding:32px 16px;">
<tr>
<td align="center">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 40px rgba(31,36,25,0.10);">

    {{-- ÜST ŞERİT (marka rengi) --}}
    <tr>
        <td style="height:5px;background-color:#b8b62e;font-size:0;line-height:0;">&nbsp;</td>
    </tr>

    {{-- LOGO + HEADER --}}
    <tr>
        <td style="padding:34px 36px 26px;text-align:center;background-color:#ffffff;border-bottom:1px solid #f0f1ec;">
            <img src="{{ mail_logo_url() }}"
                 alt="DN Kreatif İş Ortağım" width="150"
                 style="display:block;margin:0 auto 18px;max-width:150px;height:auto;border:0;">
            @if(!empty($ikon))
                <div style="font-size:40px;line-height:1;margin-bottom:10px;">{{ $ikon }}</div>
            @endif
            <h1 style="margin:0;font-size:22px;font-weight:700;color:#1f2419;letter-spacing:-0.01em;">
                {{ $baslik ?? 'İş Ortağım' }}
            </h1>
            @if(!empty($ustyazi))
                <p style="margin:8px 0 0;font-size:13px;color:#7a8270;line-height:1.5;">{{ $ustyazi }}</p>
            @endif
        </td>
    </tr>

    {{-- GÖVDE (her mail kendi icerigini buraya yazar) --}}
    <tr>
        <td style="padding:32px 36px;font-size:15px;line-height:1.7;color:#3a4133;">
            @yield('govde')
        </td>
    </tr>

    {{-- FOOTER --}}
    <tr>
        <td style="padding:24px 36px;background-color:#f7f8f3;border-top:1px solid #eceee6;text-align:center;">
            <p style="margin:0 0 6px;font-size:13px;color:#5a6150;font-weight:600;">
                İş Ortağım — DN Kreatif Yönetim Paneli
            </p>
            <p style="margin:0 0 14px;font-size:12px;color:#9aa08e;line-height:1.6;">
                Bu otomatik bir bildirim e-postasıdır. Lütfen bu adrese yanıt vermeyiniz.
            </p>
            <p style="margin:0;font-size:11px;color:#b3b8a8;">
                &copy; {{ date('Y') }} DN Grup Medya ve Teknoloji A.Ş. — Tüm hakları saklıdır.
            </p>
        </td>
    </tr>

    </table>

    {{-- alt mini not --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
    <tr>
        <td style="padding:16px 36px 0;text-align:center;">
            <p style="margin:0;font-size:11px;color:#a9af9c;line-height:1.6;">
                Bu e-postayı, İş Ortağım hesabınızla ilişkili olduğu için aldınız.
            </p>
        </td>
    </tr>
    </table>

</td>
</tr>
</table>

</body>
</html>