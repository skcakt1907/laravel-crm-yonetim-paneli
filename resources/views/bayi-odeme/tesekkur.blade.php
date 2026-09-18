<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Teşekkürler</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:linear-gradient(135deg,#10b981,#059669);min-height:100vh;display:flex;align-items:center;justify-content:center;margin:0;}
.card{background:#fff;border-radius:20px;padding:50px 40px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.2);max-width:480px;}
.icon{font-size:80px;margin-bottom:20px;}
h1{color:#065f46;margin-bottom:12px;}
p{color:#475569;line-height:1.6;}
.paket{background:#d1fae5;padding:14px;border-radius:10px;margin-top:20px;color:#065f46;font-weight:600;}
</style>
</head>
<body>
<div class="card">
    <div class="icon">✅</div>
    <h1>Ödeme Bildiriminiz Alındı</h1>
    <p>Ödemeniz kontrol edildikten sonra kısa sürede aktivasyon yapılacaktır. Teşekkür ederiz!</p>
    <div class="paket">{{ $talep->paket_adi }} — {{ number_format((float)$talep->tutar, 2, ',', '.') }} ₺</div>
</div>
</body>
</html>
