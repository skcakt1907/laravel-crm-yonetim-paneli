<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $baslik ?? 'Sayfa Bakımda' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: #fff;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            text-align: center;
        }
        
        .icon-wrapper {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #f39c12 0%, #e74c3c 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(243, 156, 18, 0.4); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 20px rgba(243, 156, 18, 0); }
        }
        
        .icon-wrapper i {
            font-size: 60px;
            color: white;
        }
        
        h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #f39c12 0%, #e74c3c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .page-name {
            display: inline-block;
            background: rgba(243, 156, 18, 0.2);
            color: #f39c12;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid rgba(243, 156, 18, 0.3);
        }
        
        .message {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .countdown {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .countdown-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 10px;
        }
        
        .countdown-time {
            font-size: 24px;
            font-weight: 700;
            color: #f39c12;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 30px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #f39c12 0%, #e74c3c 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(243, 156, 18, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 14px;
            color: rgba(255, 255, 255, 0.5);
        }
        
        @media (max-width: 480px) {
            h1 { font-size: 28px; }
            .message { font-size: 16px; }
            .actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon-wrapper">
            <i class="mdi mdi-wrench"></i>
        </div>
        
        @if(isset($sayfa_adi))
        <span class="page-name">
            <i class="mdi mdi-alert-circle"></i> {{ $sayfa_adi }}
        </span>
        @endif
        
        <h1>{{ $baslik ?? 'Sayfa Bakımda' }}</h1>
        
        <p class="message">
            {{ $mesaj ?? 'Bu sayfa şu anda bakım çalışması nedeniyle geçici olarak kapatılmıştır. Kısa süre içinde tekrar hizmetinizde olacağız.' }}
        </p>
        
        @if(isset($bitis_tarihi) && $bitis_tarihi)
        <div class="countdown">
            <div class="countdown-label">Tahmini Açılış Zamanı</div>
            <div class="countdown-time">
                {{ \Carbon\Carbon::parse($bitis_tarihi)->format('d.m.Y H:i') }}
            </div>
        </div>
        @endif
        
        <div class="actions">
            <a href="{{ url('/') }}" class="btn btn-primary">
                <i class="mdi mdi-home"></i> Ana Sayfaya Git
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <i class="mdi mdi-arrow-left"></i> Geri Dön
            </a>
        </div>
        
        <div class="footer">
            <p>Anlayışınız için teşekkür ederiz.</p>
        </div>
    </div>
</body>
</html>
