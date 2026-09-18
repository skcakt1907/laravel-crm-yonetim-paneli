<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $baslik ?? 'Site Bakımda' }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px;
        }
        .card {
            max-width: 560px;
            width: 100%;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 48px 36px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .icon {
            font-size: 80px;
            margin-bottom: 24px;
            display: inline-block;
            animation: spin 4s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        h1 {
            font-size: 32px;
            margin-bottom: 16px;
            font-weight: 700;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.95;
            margin-bottom: 16px;
        }
        .footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid rgba(255,255,255,0.2);
            font-size: 13px;
            opacity: 0.8;
        }
        .badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">⚙ HTTP 503 Service Unavailable</div>
        <div class="icon">🔧</div>
        <h1>{{ $baslik ?? 'Site Bakımda' }}</h1>
        <p>{{ $mesaj ?? 'Sitemiz şu anda bakım çalışması nedeniyle geçici olarak hizmet dışıdır. En kısa sürede tekrar hizmet vermeye başlayacağız.' }}</p>
        <p style="opacity: 0.8;">Anlayışınız için teşekkür ederiz.</p>
        <div class="footer">
            Lütfen birazdan tekrar deneyin
        </div>
    </div>
</body>
</html>
