<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('messages.maintenance_mode') }}</title>
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
        .maintenance-container {
            text-align: center;
            padding: 40px;
            max-width: 600px;
        }
        .maintenance-icon {
            width: 100px;
            height: 100px;
            background: rgba(59, 130, 246, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            animation: rotate 3s linear infinite;
        }
        .maintenance-icon svg {
            width: 50px;
            height: 50px;
            fill: #3b82f6;
        }
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .maintenance-title {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .maintenance-message {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .maintenance-info {
            background: rgba(59, 130, 246, 0.1);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
        .maintenance-info p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-icon">
            <svg viewBox="0 0 24 24"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/></svg>
        </div>
        <h1 class="maintenance-title">{{ __('messages.maintenance_mode') }}</h1>
        <p class="maintenance-message">
            {{ __('messages.maintenance_mode_description') }}
        </p>
        <div class="maintenance-info">
            <p>{{ __('messages.maintenance_thanks') }}</p>
        </div>
    </div>
</body>
</html>
