{{-- E-POSTA DOĞRULAMA — ortak layout kullanır --}}
@extends('emails.layout', [
    'baslik'  => 'E-posta Adresinizi Doğrulayın',
    'ustyazi' => 'Hesap doğrulama',
    'ikon'    => '✉️',
])

@section('govde')

    <p style="margin:0 0 18px;">
        Merhaba <strong style="color:#6f7320;">{{ $uye->ad }} {{ $uye->soyad }}</strong>,
    </p>

    <p style="margin:0 0 24px;">
        Hesabınızı oluşturduğunuz için teşekkürler! E-posta adresinizi doğrulamak için
        aşağıdaki butona tıklayın.
    </p>

    {{-- Buton --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 22px;">
    <tr><td align="center">
        <a href="{{ $verificationUrl }}"
           style="display:inline-block;background-color:#b8b62e;color:#1f2419;text-decoration:none;
                  padding:14px 36px;border-radius:10px;font-weight:700;font-size:15px;
                  box-shadow:0 6px 16px rgba(184,182,46,0.32);">
            E-posta Adresimi Doğrula
        </a>
    </td></tr>
    </table>

    <p style="margin:0 0 8px;font-size:13px;color:#7a8270;">
        Buton çalışmıyorsa, aşağıdaki bağlantıyı tarayıcınıza kopyalayın:
    </p>
    <p style="margin:0 0 22px;font-size:12px;word-break:break-all;color:#8a8a1f;">{{ $verificationUrl }}</p>

    {{-- Uyarı --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr><td style="background-color:#fff8e6;border-left:4px solid #e0a800;border-radius:8px;padding:14px 18px;">
        <p style="margin:0;font-size:13px;color:#8a6d00;line-height:1.6;">
            <strong>Önemli:</strong> Bu bağlantı 24 saat içinde geçerliliğini yitirir.
            Süresi dolduysa, giriş yaptıktan sonra yeni bir doğrulama e-postası talep edebilirsiniz.
        </p>
    </td></tr>
    </table>

@endsection