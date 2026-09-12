<?php

declare(strict_types=1);

use App\Services\Mortgage\Constants;
use App\Services\Mortgage\State;
use App\Services\Mortgage\Translations;

/** @var array<string, mixed> $v Viewmodel uit App\Services\Mortgage\ViewModel */
/** @var State $state */

$t = $v['t'];

// Alles staat in één formulier: zonder JavaScript is elke knop een submit en
// elk veld een gewone invoer. De JS-laag onderschept dezelfde elementen.
// De verborgen velden staan bewust vooraan: PHP houdt bij dubbele namen de
// laatste waarde aan, dus een zichtbaar veld wint altijd van de verborgen kopie.
$verborgen = $state->toArray();
unset($verborgen['costs'], $verborgen['parts']);
$verborgen['pay'] = $state->pay ? '1' : '';
$verborgen['pre2013'] = $state->preTwentyThirteen ? '1' : '';
unset($verborgen['preTwentyThirteen']);
foreach (['rate', 'loan', 'lender', 'ioRate'] as $sleutel) {
    if (($verborgen[$sleutel] ?? null) === null) {
        unset($verborgen[$sleutel]);
    }
}

// Kosten en leningdelen: de velden die het scherm zelf als invoer toont, laten
// we hier weg - die komen uit de zichtbare velden. De rest (de id's, de vorm en
// de aftrekbaar-schakelaar, die knoppen zijn) moet verborgen mee, anders valt
// die waarde bij een submit weg.
$toontKosten = $state->view === 'calc'
    ? $state->mode === 'advanced'
    : $state->stapSleutel() === 'lasten';
$toontDelen = $state->view === 'calc' && $state->mode === 'advanced';

$deelLijst = $state->parts ?? ($toontDelen ? (new \App\Services\Mortgage\Calculator($state))->partsData() : []);
?>
<!DOCTYPE html>
<html lang="<?= e($state->lang) ?>" data-theme="<?= e($state->theme) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($t['heroGross']) ?> — Accurity</title>
<meta name="theme-color" content="#0077B3">
<link rel="icon" href="assets/img/accurity-logo.png">
<link rel="preload" href="assets/fonts/schibsted-grotesk-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="assets/fonts/jetbrains-mono-latin.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="assets/css/app.css">
</head>
<body>
<form id="app" method="post" action="index.php" autocomplete="off">
@csrf
<?php foreach ($verborgen as $naam => $waarde): ?>
<input type="hidden" name="<?= e($naam) ?>" value="<?= e(is_bool($waarde) ? ($waarde ? '1' : '') : (string)$waarde) ?>">
<?php endforeach; ?>
<?php foreach ($state->kostenLijst() as $post): ?>
<input type="hidden" name="costs[<?= (int)$post['id'] ?>][id]" value="<?= (int)$post['id'] ?>">
    <?php if (!$toontKosten): ?>
<input type="hidden" name="costs[<?= (int)$post['id'] ?>][label]" value="<?= e($post['label']) ?>">
<input type="hidden" name="costs[<?= (int)$post['id'] ?>][amount]" value="<?= e((string)$post['amount']) ?>">
    <?php endif; ?>
<?php endforeach; ?>
<?php foreach ($deelLijst as $deel): ?>
<input type="hidden" name="parts[<?= (int)$deel['id'] ?>][id]" value="<?= (int)$deel['id'] ?>">
<input type="hidden" name="parts[<?= (int)$deel['id'] ?>][form]" value="<?= e((string)$deel['form']) ?>">
<input type="hidden" name="parts[<?= (int)$deel['id'] ?>][deductible]" value="<?= !empty($deel['deductible']) ? '1' : '' ?>">
<input type="hidden" name="parts[<?= (int)$deel['id'] ?>][dedMonths]" value="<?= (int)($deel['dedMonths'] ?? 360) ?>">
    <?php if (!$toontDelen): ?>
<input type="hidden" name="parts[<?= (int)$deel['id'] ?>][sum]" value="<?= e((string)$deel['sum']) ?>">
<input type="hidden" name="parts[<?= (int)$deel['id'] ?>][rate]" value="<?= e((string)$deel['rate']) ?>">
<input type="hidden" name="parts[<?= (int)$deel['id'] ?>][term]" value="<?= e((string)$deel['term']) ?>">
<input type="hidden" name="parts[<?= (int)$deel['id'] ?>][elapsed]" value="<?= e((string)$deel['elapsed']) ?>">
    <?php endif; ?>
<?php endforeach; ?>

@include('design.header')
@include('design.wizard')
@include('design.result')
@include('design.paywall')

@include('design.item-templates')
<script type="application/json" id="design-data"><?= json_encode([
    'constants'    => Constants::forJs(),
    'translations' => Translations::alle(),
    'state'        => $state->toArray(),
    'costs'        => $state->kostenLijst(),
    'lenders'      => array_map(
        static fn (array $rij): array => ['name' => $rij['name'], 'delta' => $rij['delta'], 'note' => $rij['note']],
        array_values(array_filter(require base_path('data/lenders.php'), static fn (array $r): bool => !empty($r['active'])))
    ),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="assets/js/calc.js"></script>
<script src="assets/js/viewmodel.js"></script>
<script src="assets/js/app.js"></script>
</form>
</body>
</html>
