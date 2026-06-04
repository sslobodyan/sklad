<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Сторінку не знайдено</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .error-card {
            background: white;
            border-radius: 16px;
            padding: 40px 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e0e0e0;
        }
        .error-code {
            font-size: 72px;
            font-weight: 700;
            color: #c62828;
            margin-bottom: 16px;
        }
        .error-title {
            font-size: 24px;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 12px;
        }
        .error-message {
            font-size: 14px;
            color: #666;
            margin-bottom: 24px;
            line-height: 1.5;
        }
        .error-url {
            background: #f5f5f5;
            padding: 12px 16px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #333;
            word-break: break-all;
            margin-bottom: 24px;
            border: 1px solid #e8e8e8;
        }
        .error-url-label {
            font-size: 11px;
            color: #999;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #0082c9;
            color: white;
            border: none;
        }
        .btn-primary:hover {
            background: #006aa3;
            text-decoration: none;
        }
        .btn-secondary {
            background: white;
            color: #333;
            border: 1px solid #ddd;
            margin-left: 8px;
        }
        .btn-secondary:hover {
            background: #f5f5f5;
            border-color: #ccc;
            text-decoration: none;
        }
        .buttons {
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-code">404</div>
            <div class="error-title">Сторінку не знайдено</div>
            <div class="error-message">
                Запитана сторінка не існує або була переміщена.
            </div>
            
            <div class="error-url">
                <div class="error-url-label">ЗАПИТАНИЙ URL</div>
                <div><?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?></div>
            </div>
            
            <div class="buttons">
                <a href="<?= BASE_PATH ?>/dashboard" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9l9-6 9 6v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    На головну
                </a>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    Назад
                </a>
            </div>
        </div>
    </div>
</body>
</html>