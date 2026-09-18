@php
    // abort(403, '...') ile gelen mesaj; yoksa varsayilan
    $mesaj = ($exception ?? null) && method_exists($exception, 'getMessage') && trim($exception->getMessage()) !== ''
        ? $exception->getMessage()
        : 'Bu sayfaya erişim yetkiniz yok.';
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>403 — Erişim Engellendi · İş Ortağım</title>
<link rel="shortcut icon" href="{{ asset('tema/uploads/favicon/favicon_dn.png') }}">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
    --brand:#b8b62e; --brand-dark:#8a8a1f; --brand-soft:rgba(184,182,46,.10);
    --bg:#f6f7f2; --surface:#ffffff; --border:rgba(15,23,42,.08);
    --text:#0f172a; --text-muted:#64748b; --danger:#ef4444;
}
body.theme-dark{
    --bg:#0a0a0a; --surface:#141414; --border:rgba(255,255,255,.08);
    --text:#f1f5f9; --text-muted:#94a3b8;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-font-smoothing:antialiased}
body{
    font-family:'Poppins',system-ui,sans-serif;background:var(--bg);color:var(--text);
    min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
    position:relative;overflow:hidden;
}
/* arka plan dokusu: yumusak lime izgara + halka */
body::before{
    content:'';position:fixed;inset:0;z-index:0;
    background-image:
        radial-gradient(circle at 85% 15%, var(--brand-soft) 0%, transparent 45%),
        radial-gradient(circle at 15% 90%, var(--brand-soft) 0%, transparent 40%);
}
.wrap{position:relative;z-index:1;max-width:560px;width:100%;text-align:center}
.lock{
    width:96px;height:96px;margin:0 auto 28px;border-radius:28px;
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(135deg,var(--brand),var(--brand-dark));
    box-shadow:0 20px 50px rgba(184,182,46,.32);
    animation:pop .5s cubic-bezier(.34,1.56,.64,1) both;
    position:relative;
}
.lock svg{width:46px;height:46px;stroke:#1a1a08;stroke-width:2.2;fill:none}
.lock::after{
    content:'403';position:absolute;bottom:-10px;right:-10px;
    background:var(--surface);color:var(--danger);font-weight:800;font-size:13px;
    padding:4px 10px;border-radius:999px;border:1px solid var(--border);
    box-shadow:0 4px 14px rgba(0,0,0,.1);
}
h1{
    font-size:30px;font-weight:800;letter-spacing:-.03em;margin-bottom:12px;
    animation:rise .5s ease .08s both;
}
.msg{
    font-size:15px;line-height:1.7;color:var(--text-muted);
    max-width:420px;margin:0 auto 32px;animation:rise .5s ease .16s both;
}
.actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;animation:rise .5s ease .24s both}
.btn{
    display:inline-flex;align-items:center;gap:8px;text-decoration:none;
    font-family:inherit;font-weight:600;font-size:14px;padding:13px 26px;border-radius:12px;
    transition:transform .15s ease,box-shadow .15s ease;cursor:pointer;border:none;
}
.btn-primary{
    background:linear-gradient(135deg,var(--brand),var(--brand-dark));color:#1a1a08;
    box-shadow:0 10px 24px rgba(184,182,46,.3);
}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 14px 32px rgba(184,182,46,.42)}
.btn-ghost{
    background:var(--surface);color:var(--text);border:1px solid var(--border);
}
.btn-ghost:hover{transform:translateY(-2px);border-color:var(--brand)}
.hint{margin-top:28px;font-size:12.5px;color:var(--text-muted);animation:rise .5s ease .32s both}
@keyframes pop{from{opacity:0;transform:scale(.7) rotate(-8deg)}to{opacity:1;transform:scale(1) rotate(0)}}
@keyframes rise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>
<body>
<div class="wrap">
    <div class="lock">
        <svg viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V7a4 4 0 0 1 8 0v4"></path></svg>
    </div>
    <h1>Erişim Engellendi</h1>
    <p class="msg">{{ $mesaj }}</p>
    <div class="actions">
        <a href="{{ url('/admin/dashboard') }}" class="btn btn-primary">
            ← Panele Dön
        </a>
        <a href="javascript:history.back()" class="btn btn-ghost">
            Geri Git
        </a>
    </div>
    <div class="hint">Bu sayfaya erişmen gerekiyorsa patrondan yetki talep edebilirsin.</div>
</div>
<script>
    if (localStorage.getItem('admin_theme') === 'dark') document.body.classList.add('theme-dark');
</script>
</body>
</html>