<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $cikildi ? 'Abonelikten Çıkıldı' : 'Bülten Aboneliği' }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:#f6f7f2;
             min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;color:#1a1a0e}
        .kutu{background:#fff;border:1px solid #e6e7de;border-radius:16px;padding:40px 34px;
              max-width:460px;width:100%;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,.06)}
        .ikon{width:64px;height:64px;border-radius:50%;margin:0 auto 22px;
              display:flex;align-items:center;justify-content:center;font-size:30px}
        .ikon.ok{background:rgba(16,185,129,.12)}
        .ikon.sor{background:rgba(184,182,46,.15)}
        h1{font-size:21px;font-weight:700;margin-bottom:12px}
        p{font-size:14.5px;line-height:1.65;color:#5b6168;margin-bottom:10px}
        .mail{display:inline-block;background:#f6f7f2;border:1px solid #e6e7de;border-radius:8px;
              padding:8px 14px;font-size:13.5px;font-weight:600;margin:6px 0 22px;word-break:break-all}
        .btn{display:inline-block;padding:13px 30px;background:#b8b62e;color:#1a1a0e;border:none;
             border-radius:10px;font-weight:700;font-size:15px;cursor:pointer;text-decoration:none;
             font-family:inherit;transition:.2s}
        .btn:hover{background:#a3a128}
        .alt{display:block;margin-top:16px;font-size:13px;color:#8a9098;text-decoration:none}
        .alt:hover{text-decoration:underline}
        .not{margin-top:22px;padding-top:18px;border-top:1px solid #eceee6;font-size:12.5px;color:#8a9098}
    </style>
</head>
<body>
<div class="kutu">
    @if($cikildi)
        <div class="ikon ok">✓</div>
        <h1>Abonelikten çıkarıldınız</h1>
        <p>Bundan sonra size <strong>tanıtım ve kampanya</strong> e-postası göndermeyeceğiz.</p>
        <div class="mail">{{ $email }}</div>
        <p class="not">
            Siparişleriniz, faturalarınız ve güvenlik bildirimleri (şifre sıfırlama,
            doğrulama kodu gibi) gönderilmeye devam eder — bunlar hesabınızın
            güvenliği için gereklidir.
        </p>
        <a class="alt" href="{{ url('/') }}">Ana sayfaya dön</a>
    @else
        <div class="ikon sor">✉</div>
        <h1>Bülten aboneliğinden çıkmak istiyor musunuz?</h1>
        <p>Bu adrese artık tanıtım ve kampanya e-postası gönderilmeyecek:</p>
        <div class="mail">{{ $email }}</div>
        <form method="POST" action="{{ request()->fullUrl() }}">
            @csrf
            <button type="submit" class="btn">Evet, abonelikten çık</button>
        </form>
        <a class="alt" href="{{ url('/') }}">Vazgeç, ana sayfaya dön</a>
    @endif
</div>
</body>
</html>
