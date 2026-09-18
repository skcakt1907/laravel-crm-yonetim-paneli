<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('tema/uploads/favicon/favicon_dn.png') }}">
    <link rel="icon" type="image/png" href="{{ asset('tema/uploads/favicon/favicon_dn.png') }}">
    <title>Şifre Sıfırlama — İş Ortağım Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    <style>
        :root {
            --brand: #b8b62e;
            --brand-dark: #8a8a1f;
            --brand-soft: rgba(184,182,46,.12);
            --bg: #f6f7f3;
            --card-bg: #ffffff;
            --text: #1a1d24;
            --text-soft: #4b5563;
            --text-muted: #6b7280;
            --border: #e5e7eb;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 12px 40px rgba(15,23,42,.08);
        }
        .reset-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }
        .reset-logo-mark {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--brand), var(--brand-dark));
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 18px;
        }
        .reset-logo-text { font-weight: 800; font-size: 17px; }
        .reset-logo-text small { display: block; font-size: 11px; color: var(--brand-dark); font-weight: 600; letter-spacing: .08em; }
        h1 { font-size: 22px; font-weight: 800; margin-bottom: 6px; }
        .sub { color: var(--text-muted); font-size: 14px; margin-bottom: 26px; }
        .form-label { display: block; font-size: 13px; font-weight: 700; color: var(--text-soft); margin-bottom: 8px; }
        .form-input {
            width: 100%;
            background: #f9f9f4;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 13px 16px;
            font-size: 14.5px;
            color: var(--text);
            outline: none;
            transition: border-color .2s;
        }
        .form-input:focus { border-color: var(--brand); }
        .primary-btn {
            width: 100%;
            margin-top: 20px;
            background: var(--brand);
            color: #1a1a0e;
            font-weight: 800;
            font-size: 15px;
            padding: 14px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 8px;
            transition: background .2s, transform .2s;
        }
        .primary-btn:hover { background: var(--brand-dark); transform: translateY(-1px); }
        .alert { padding: 13px 16px; border-radius: 10px; font-size: 14px; margin-bottom: 20px; }
        .alert-error { background: #fdecec; border: 1px solid #e24b4a; color: #a32d2d; }
        .alert-success { background: #e6f7ef; border: 1px solid #1d9e75; color: #0f6e56; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; margin-top: 22px; color: var(--text-muted); text-decoration: none; font-size: 13.5px; font-weight: 600; }
        .back-link:hover { color: var(--brand-dark); }
        .hint { font-size: 12.5px; color: var(--text-muted); margin-top: 8px; }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="reset-logo">
            <div class="reset-logo-mark">io</div>
            <div class="reset-logo-text">İş Ortağım<small>ADMİN PANEL</small></div>
        </div>

        <h1>Şifre Sıfırlama</h1>
        <p class="sub">Kayıtlı e-posta adresinizi girin, size doğrulama kodu gönderelim.</p>

        @if(session('error'))
            <div class="alert alert-error"><i class="mdi mdi-alert-circle"></i> {{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert alert-success"><i class="mdi mdi-check-circle"></i> {{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('admin.sifre.sifirlama.kod.gonder') }}">
            @csrf
            <label class="form-label"><i class="mdi mdi-email"></i> E-posta Adresi</label>
            <input type="email" name="email" class="form-input" value="{{ old('email') }}" required autofocus placeholder="ornek@email.com">
            @error('email')<div class="hint" style="color:#a32d2d;">{{ $message }}</div>@enderror

            <button type="submit" class="primary-btn"><i class="mdi mdi-send"></i> Doğrulama Kodu Gönder</button>
        </form>

        <a href="{{ route('admin.giris') }}" class="back-link"><i class="mdi mdi-arrow-left"></i> Giriş sayfasına dön</a>
    </div>
</body>
</html>