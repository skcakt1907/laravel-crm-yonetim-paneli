{{--
    HOŞGELDİN MAILI
    Kullanim:
        Mail::send('emails.hosgeldin', [
            'ad'        => $uye->ad,
            'girisUrl'  => route('giris'),   // opsiyonel buton linki
        ], function($m) use ($uye) {
            $m->to($uye->email)->subject('İş Ortağım\'a Hoş Geldiniz!');
        });
--}}
@extends('emails.layout', [
    'baslik'  => 'Hoş Geldiniz!',
    'ustyazi' => 'Aramıza katıldığınız için teşekkürler',
    'ikon'    => '🎉',
])

@section('govde')

    <p style="margin:0 0 18px;">
        Merhaba <strong style="color:#6f7320;">{{ $ad ?? 'Değerli üyemiz' }}</strong>,
    </p>

    <p style="margin:0 0 18px;">
        İş Ortağım ailesine hoş geldiniz! Hesabınız başarıyla oluşturuldu.
        Artık tüm hizmetlerimizden yararlanabilirsiniz.
    </p>

    <p style="margin:0 0 26px;">
        Başlamak için hesabınıza giriş yapabilir, profilinizi tamamlayabilir
        ve size özel fırsatları keşfedebilirsiniz.
    </p>

    {{-- BUTON (kalip: her mailde boyle kullanilir) --}}
    @if(!empty($girisUrl))
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 26px;">
    <tr>
        <td align="center">
            <a href="{{ $girisUrl }}"
               style="display:inline-block;background-color:#b8b62e;color:#1f2419;text-decoration:none;
                      padding:15px 38px;border-radius:10px;font-weight:700;font-size:15px;
                      box-shadow:0 6px 16px rgba(184,182,46,0.35);">
                Hesabıma Giriş Yap
            </a>
        </td>
    </tr>
    </table>
    @endif

    <p style="margin:0;font-size:14px;color:#7a8270;">
        Herhangi bir sorunuz olursa destek ekibimiz size yardımcı olmaktan mutluluk duyar.
    </p>

@endsection