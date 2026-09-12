<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Hypotheek\CalculationRequest;
use Hypotheek\Formatter;
use Hypotheek\LoanPart;
use Hypotheek\Mortgage;
use Hypotheek\MortgageResult;

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$request = $isPost ? CalculationRequest::fromPost($_POST) : CalculationRequest::leeg();

/** @var MortgageResult|null $resultaat */
$resultaat = null;

if ($isPost && $request->valideer()) {
    $parts = $request->loanParts();

    // Bepaal eerst de looptijd, zodat de inkomensreeks precies zo lang loopt.
    $maxMaanden = 0;
    foreach ($parts as $part) {
        $maxMaanden = max($maxMaanden, $part->resterendeMaanden());
    }
    $eindJaar = $request->startJaar + (int)ceil(($request->startMaand - 1 + $maxMaanden) / 12);

    $mortgage = new Mortgage(
        parts: $parts,
        startJaar: $request->startJaar,
        startMaand: $request->startMaand,
        maandkosten: $request->monthlyCosts(),
        inkomenPerJaar: $request->inkomenPerJaar($request->startJaar, $eindJaar),
        wozWaarde: $request->wozWaarde,
    );
    $resultaat = $mortgage->bereken();

    if (($_POST['export'] ?? '') === 'csv') {
        exportCsv($resultaat);
        exit;
    }
}

function exportCsv(MortgageResult $resultaat): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="jaaroverzicht-hypotheek.csv"');

    $out = fopen('php://output', 'wb');
    fwrite($out, "\xEF\xBB\xBF"); // BOM, zodat Excel de accenten goed leest

    $kop = [
        'Jaar', 'Maanden', 'Restschuld begin', 'Rente', 'Aflossing', 'Bruto jaarlast',
        'Bruto maandlast', 'Overige maandkosten', 'Restschuld eind',
    ];
    if ($resultaat->fiscaalBerekend) {
        $kop = array_merge($kop, [
            'Aftrekbare rente', 'Eigenwoningforfait', 'Aftrekpost',
            'Belastingvoordeel jaar', 'Netto maandlast',
        ]);
    }
    fputcsv($out, $kop, ';', '"', '');

    foreach ($resultaat->jaren as $jaar) {
        $rij = [
            $jaar->jaar, $jaar->maanden,
            round($jaar->restschuldBegin, 2), round($jaar->rente, 2), round($jaar->aflossing, 2),
            round($jaar->brutoJaarlast(), 2), round($jaar->brutoMaandlast(), 2),
            round($jaar->overigeMaandkosten(), 2), round($jaar->restschuldEind, 2),
        ];
        if ($resultaat->fiscaalBerekend) {
            $f = $jaar->fiscaal;
            $rij = array_merge($rij, [
                round($jaar->aftrekbareRente, 2),
                round($f?->eigenwoningforfait ?? 0.0, 2),
                round($f?->aftrekpost ?? 0.0, 2),
                round($jaar->belastingvoordeelJaar(), 2),
                round($jaar->nettoMaandlast(), 2),
            ]);
        }
        fputcsv($out, $rij, ';', '"', '');
    }
    fclose($out);
}

$h = static fn (?string $s): string => Formatter::h($s);

require __DIR__ . '/../templates/layout.php';
