<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 — Сторінку не знайдено</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f0f2f5;
            color: #1a1a1a;
        }
        .container { text-align: center; }
        h1 { font-size: 5rem; color: #0082c9; margin: 0; font-weight: 700; }
        p { color: #5c5c5c; margin: 10px 0 20px; }
        a {
            display: inline-block;
            padding: 10px 20px;
            background: #0082c9;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
        }
        a:hover { background: #006aa3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <p>Сторінку не знайдено</p>
        <a href="<?= rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') ?>/">← На головну</a>
    </div>
</body>
</html>
