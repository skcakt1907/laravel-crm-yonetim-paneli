<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oturum Zaman Aşımı</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            max-width: 600px;
        }
        .error-icon {
            width: 80px;
            height: 80px;
            background: rgba(251, 191, 36, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        .error-icon svg {
            width: 40px;
            height: 40px;
            fill: #fbbf24;
        }
        .error-code {
            font-size: 100px;
            font-weight: 700;
            background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #f1f5f9;
        }
        .error-message {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .btn-refresh {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #d4d25b 0%, #c4c24b 100%);
            color: #000;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        .btn-refresh:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(212, 210, 91, 0.3);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
        </div>
        <div class="error-code">419</div>
        <h1 class="error-title">Oturum Zaman Aşımı</h1>
        <p class="error-message">
            Güvenlik nedeniyle oturumunuz sona erdi. Lütfen sayfayı yenileyip tekrar deneyin.
        </p>
        <button onclick="window.location.reload()" class="btn-refresh">Sayfayı Yenile</button>
    </div>
</body>
</html>
