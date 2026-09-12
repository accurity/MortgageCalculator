<?php

declare(strict_types=1);

use Hypotheek\Formatter;

/** @var \Hypotheek\CalculationRequest $request */
/** @var \Hypotheek\MortgageResult|null $resultaat */
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hypotheekcalculator | Accurity Online Communicatie</title>
<meta name="theme-color" content="#027cb5">
<link rel="icon" href="assets/img/accurity-logo.png">
<link rel="preload" href="assets/fonts/bitter-variable.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="site-header">
    <span class="merk">
        <img src="assets/img/accurity-logo.png" alt="Accurity Online Communicatie" width="1845" height="322">
    </span>
    <h1>Hypotheekcalculator</h1>
    <p>Reken meerdere leningdelen door: annuïtair, lineair en aflossingsvrij. Met jaaroverzicht,
       maandlasten en - als je een inkomen invult - het effect van de hypotheekrenteaftrek.</p>
</header>

<main>
<?php if ($request->fouten !== []): ?>
    <div class="melding melding-fout">
        <strong>Controleer de invoer:</strong>
        <ul>
            <?php foreach ($request->fouten as $fout): ?>
                <li><?= Formatter::h($fout) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/form.php'; ?>

<?php if ($resultaat !== null): ?>
    <?php require __DIR__ . '/results.php'; ?>
<?php endif; ?>
</main>

<footer class="site-footer">
    <p>Indicatieve berekening. Geen financieel advies. Fiscale tarieven staan in
       <code>src/TaxRules.php</code> en moeten per jaar gecontroleerd worden bij de Belastingdienst.</p>
    <p>&copy; <?= date('Y') ?> Accurity Online Communicatie</p>
</footer>

<script src="assets/app.js"></script>
</body>
</html>
