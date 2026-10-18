<?php
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$score = 0;
$feedback = '';

if ($submitted) {
    $q1 = $_POST['q1'] ?? '';
    $q2 = $_POST['q2'] ?? '';
    
    if (strtolower(trim($q1)) === 'php') $score += 50;
    if ($q2 === 'hypertext') $score += 50;
    
    $feedback = "You scored {$score}%!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Quick Quiz</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; padding: 2rem; max-width: 600px; margin: 0 auto; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .question { margin-bottom: 1.5rem; }
        label { display: block; margin: 0.4rem 0; cursor: pointer; }
        input[type="text"] { width: 100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        button { background: #4f46e5; color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .score-box { background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; font-weight: 700; margin-bottom: 1.5rem; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h1>📝 Classroom Quiz</h1>
        
        <?php if ($submitted): ?>
            <div class="score-box"><?= $feedback ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="question">
                <p><strong>1. What server-side language powers this micro-site?</strong></p>
                <input type="text" name="q1" placeholder="Type language name..." required value="<?= htmlspecialchars($_POST['q1'] ?? '') ?>">
            </div>

            <div class="question">
                <p><strong>2. What does the "H" in HTML stand for?</strong></p>
                <label><input type="radio" name="q2" value="hypertext" required> Hypertext</label>
                <label><input type="radio" name="q2" value="hybrid"> Hybrid</label>
                <label><input type="radio" name="q2" value="hosting"> Hosting</label>
            </div>

            <button type="submit">Submit Answers</button>
        </form>
    </div>
</body>
</html>