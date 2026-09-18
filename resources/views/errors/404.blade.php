<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Sayfa Bulunamadı</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('yonetim/vendors/iconfonts/mdi/font/css/materialdesignicons.min.css') }}">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',sans-serif;background:#ffffff;color:#0f0f0f;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;}
        .num{font-family:'Syne',sans-serif;font-size:clamp(90px,16vw,160px);font-weight:800;color:#f5c000;line-height:1;margin-bottom:20px;}
        h1{font-family:'Syne',sans-serif;font-size:clamp(20px,3vw,32px);font-weight:800;color:#0f0f0f;margin-bottom:12px;}
        p{font-size:16px;color:#71717a;line-height:1.7;margin-bottom:36px;max-width:440px;text-align:center;}
        .btns{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;}
        .btn-y{display:inline-flex;align-items:center;gap:8px;padding:13px 28px;background:#f5c000;color:#0f0f0f;font-weight:700;font-size:15px;border-radius:12px;text-decoration:none;transition:background .2s;}
        .btn-y:hover{background:#e6b400;}
        .btn-o{display:inline-flex;align-items:center;gap:8px;padding:12px 26px;border:1.5px solid #d4d4d8;color:#0f0f0f;font-weight:600;font-size:15px;border-radius:12px;text-decoration:none;transition:all .2s;}
        .btn-o:hover{border-color:#0f0f0f;background:#fafafa;}
    </style>
</head>
<body>
    <div class="num">404</div>
    <h1>Sayfa Bulunamadı</h1>
    <p>Aradığınız sayfa taşınmış, silinmiş ya da hiç var olmamış olabilir.</p>
    <div class="btns">
        <a href="/" class="btn-y">← Ana Sayfaya Dön</a>
        <a href="/iletisim" class="btn-o">Bize Ulaşın</a>
    </div>
</body>
</html>
