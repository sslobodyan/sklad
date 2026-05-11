<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Помилка</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-box {
            background: white;
            padding: 32px;
            border-radius: 16px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .error-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .error-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #c62828;
        }
        .error-message {
            color: #666;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon">⚠️</div>
        <div class="error-title">Помилка налаштування</div>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    </div>
</body>
</html>
