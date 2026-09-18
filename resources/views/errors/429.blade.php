<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Çok Fazla İstek</title>
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
            background: rgba(239, 68, 68, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }
        .error-icon svg {
            width: 40px;
            height: 40px;
            fill: #ef4444;
        }
        .error-code {
            font-size: 100px;
            font-weight: 700;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
        .countdown {
            font-size: 48px;
            font-weight: 700;
            color: #d4d25b;
            margin-bottom: 20px;
        }
        .btn-home {
            display: inline-block;
            padding: 14px 32px;
            background: linear-gradient(135deg, #d4d25b 0%, #c4c24b 100%);
            color: #000;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(212, 210, 91, 0.3);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 5h2v6h-2zm0 8h2v2h-2z"/></svg>
        </div>
        <div class="error-code">429</div>
        <h1 class="error-title">Çok Fazla İstek</h1>
        <p class="error-message">
            Kısa sürede çok fazla istek gönderdiniz. Lütfen bir süre bekleyip tekrar deneyin.
        </p>
        <div class="countdown" id="countdown">60</div>
        <a href="/" class="btn-home">Ana Sayfaya Dön</a>
    </div>
    <script>
        let seconds = 60;
        const countdown = document.getElementById('countdown');
        const timer = setInterval(() => {
            seconds--;
            countdown.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.reload();
            }
        }, 1000);
    </script>
</body>
</html>
