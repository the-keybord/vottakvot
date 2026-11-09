<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Micro Site</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 1rem; }
        .card { max-width: 600px; width: 100%; background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.3); text-align: center; }
        h1 { color: #38bdf8; margin-bottom: 0.5rem; }
        p { color: #94a3b8; line-height: 1.6; }
        .badge { background: #0284c7; color: white; padding: 0.3rem 0.8rem; border-radius: 999px; font-size: 0.85rem; font-weight: 600; display: inline-block; margin-bottom: 1rem; }
        .info { background: #0f172a; padding: 1rem; border-radius: 8px; margin: 1.5rem 0; font-family: monospace; color: #38bdf8; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">🌐 TikTak Micro-Site</span>
        <h1>Welcome to My Micro-site!</h1>
        <p>This page is hosted dynamically on the server. You can edit this file, add HTML, CSS, JavaScript, and execute PHP.</p>
        <div class="info">
            Server Time: <?= date('Y-m-d H:i:s') ?><br>
            PHP Version: <?= phpversion() ?>
        </div>
        <p style="font-size: 0.9rem;">Edit this file in your Professor Dashboard.</p>
    </div>
</body>
</html>