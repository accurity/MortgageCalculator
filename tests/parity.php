<?php

declare(strict_types=1);

/**
 * Vergelijkt de PHP-rekenkern met de JS-rekenkern (public/assets/js/calc.js).
 *
 * Beide kanten moeten op de cent hetzelfde uitkomen; wijkt er iets af, dan is
 * een van de twee ports gaan schuiven. Node is nodig; zonder node slaat het
 * script zichzelf over.
 *
 * Draaien: php tests/parity.php
 */

require __DIR__ . '/../src/bootstrap.php';

use Hypotheek\Design\Calculator;
use Hypotheek\Design\Constants;
use Hypotheek\Design\State;
use Hypotheek\Design\Translations;
use Hypotheek\Design\ViewModel;

$gevallen = [
    ['naam' => 'standaard kopen',        'in' => []],
    ['naam' => 'kopen, veel eigen geld', 'in' => ['price' => '600000', 'own' => '250000']],
    ['naam' => 'kopen, weinig inleg',    'in' => ['price' => '300000', 'own' => '5000']],
    ['naam' => 'kopen, lineair',         'in' => ['form' => 'lin']],
    ['naam' => 'kopen, aflossingsvrij',  'in' => ['form' => 'av']],
    ['naam' => 'kopen, io-deel',         'in' => ['io' => '100000']],
    ['naam' => 'kopen, io op maximum',   'in' => ['io' => '250000']],
    ['naam' => 'kopen, 1 jaar vast',     'in' => ['fixedY' => '1']],
    ['naam' => 'kopen, 30 jaar vast',    'in' => ['fixedY' => '30']],
    ['naam' => 'kopen, korte looptijd',  'in' => ['termY' => '10']],
    ['naam' => 'kopen, eigen rente',     'in' => ['rate' => '5.35']],
    ['naam' => 'kopen, inkomen',         'in' => ['income' => '68000']],
    ['naam' => 'kopen, twee inkomens',   'in' => ['income' => '48000', 'income2' => '39000']],
    ['naam' => 'kopen, hoog inkomen',    'in' => ['income' => '145000']],
    ['naam' => 'kopen, lage woz',        'in' => ['income' => '68000', 'woz' => '210000']],
    ['naam' => 'oversluiten standaard',  'in' => ['path' => 'renew']],
    ['naam' => 'oversluiten, lineair',   'in' => ['path' => 'renew', 'curForm' => 'lin', 'form' => 'lin']],
    ['naam' => 'oversluiten, pre-2013',  'in' => ['path' => 'renew', 'pre2013' => '1', 'io' => '80000']],
    ['naam' => 'oversluiten, kort rest', 'in' => ['path' => 'renew', 'curRemaining' => '8']],
    ['naam' => 'oversluiten + inkomen',  'in' => ['path' => 'renew', 'income' => '72000', 'io' => '60000']],
    ['naam' => 'eigen leningdelen',      'in' => [
        'parts' => [
            ['id' => 0, 'form' => 'ann', 'sum' => '250000', 'rate' => '3,9', 'term' => '30', 'elapsed' => '48', 'deductible' => '1', 'dedMonths' => '360'],
            ['id' => 1, 'form' => 'av',  'sum' => '100000', 'rate' => '4,1', 'term' => '30', 'elapsed' => '48', 'deductible' => '',  'dedMonths' => '360'],
        ],
        'income' => '80000',
    ]],
    ['naam' => 'eigen kosten',           'in' => [
        'costs' => [
            ['id' => 1, 'label' => 'VvE', 'amount' => '145'],
            ['id' => 2, 'label' => 'Onderhoud', 'amount' => '100'],
            ['id' => 3, 'label' => 'Waterschap', 'amount' => '22'],
        ],
    ]],
];

/** @param array<string, mixed> $rij */
function phpKant(array $in): array
{
    $state = State::uitRequest($in);
    $calc = new Calculator($state);
    $C = $calc->compute();

    $totaleRente = 0.0;
    foreach ($C['years'] as $jaar) {
        $totaleRente += $jaar['interest'];
    }

    return [
        'totalLoan'      => $calc->totalLoan(),
        'ltv'            => $calc->ltv(),
        'rate'           => $calc->rate(),
        'ioMax'          => $calc->ioMax(),
        'termY'          => $C['termY'],
        'grossMonthly'   => $C['grossMonthly'],
        'grossTotal'     => $C['grossTotal'],
        'netTotal'       => $C['netTotal'],
        'interestY1'     => $C['interestY1'],
        'principalY1'    => $C['principalY1'],
        'costTotal'      => $C['costTotal'],
        'marginal'       => $C['marginal'],
        'ewf'            => $C['ewf'],
        'dedInterest'    => $C['dedInterest'],
        'benefit'        => $C['benefit'],
        'residual'       => $C['residual'],
        'years'          => count($C['years']),
        'lastBalance'    => $C['years'] === [] ? 0.0 : $C['years'][count($C['years']) - 1]['balance'],
        'totalInterest'  => $totaleRente,
        'currentPayment' => $calc->currentPayment(),
        'afterFixUp'     => $calc->afterFix(1.0),
        'afterFixDown'   => $calc->afterFix(-1.0),
    ];
}

$cases = [];
$phpUit = [];
foreach ($gevallen as $geval) {
    $state = State::uitRequest($geval['in']);
    $cases[] = [
        'name'  => $geval['naam'],
        'state' => $state->toArray(),
        'costs' => $state->kostenLijst(),
    ];
    $phpUit[] = phpKant($geval['in']);
}

$payload = [
    'constants'    => Constants::forJs(),
    'translations' => Translations::alle(),
    'lenders'      => array_map(
        static fn (array $rij): array => ['name' => $rij['name'], 'delta' => $rij['delta'], 'note' => $rij['note']],
        array_values(array_filter(require __DIR__ . '/../data/lenders.php', static fn (array $r): bool => !empty($r['active'])))
    ),
    'cases'        => $cases,
];
$tmp = sys_get_temp_dir() . '/hypotheek-parity-' . getmypid() . '.json';
file_put_contents($tmp, json_encode($payload, JSON_THROW_ON_ERROR));

exec('command -v node 2>/dev/null', $waar, $status);
if ($status !== 0) {
    fwrite(STDERR, "node niet gevonden - pariteitstest overgeslagen.\n");
    unlink($tmp);
    exit(0);
}

$cmd = sprintf('node %s %s 2>&1', escapeshellarg(__DIR__ . '/parity-node.js'), escapeshellarg($tmp));
$ruw = shell_exec($cmd);
unlink($tmp);

if (!is_string($ruw) || $ruw === '') {
    fwrite(STDERR, "node gaf geen uitvoer.\n");
    exit(1);
}

try {
    /** @var list<array<string, mixed>> $jsUit */
    $jsUit = json_decode($ruw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    fwrite(STDERR, "node gaf geen JSON terug:\n" . $ruw . "\n");
    exit(1);
}

/**
 * Loopt de sleutels van het viewmodel na. Alleen wat op het scherm terechtkomt
 * telt mee: teksten, kleuren en de lengte van de lijsten.
 *
 * @param array<string, mixed> $php
 * @param array<string, mixed> $js
 * @return list<string>
 */
function vergelijkViewmodel(array $php, array $js, string $pad = ''): array
{
    $meldingen = [];

    foreach ($php as $sleutel => $waarde) {
        $plek = $pad === '' ? (string)$sleutel : $pad . '.' . $sleutel;
        if (!array_key_exists($sleutel, $js)) {
            $meldingen[] = $plek . ' ontbreekt in JS';
            continue;
        }
        $ander = $js[$sleutel];

        if (is_array($waarde)) {
            if (!is_array($ander)) {
                $meldingen[] = $plek . ': JS is geen lijst';
                continue;
            }
            if (array_is_list($waarde) && count($waarde) !== count($ander)) {
                $meldingen[] = sprintf('%s: php heeft %d items, js %d', $plek, count($waarde), count($ander));
                continue;
            }
            foreach (vergelijkViewmodel($waarde, $ander, $plek) as $m) {
                $meldingen[] = $m;
            }
            continue;
        }

        if (is_bool($waarde)) {
            if ($waarde !== (bool)$ander) {
                $meldingen[] = sprintf('%s: php=%s js=%s', $plek, $waarde ? 'true' : 'false', $ander ? 'true' : 'false');
            }
            continue;
        }

        if (is_float($waarde) || is_int($waarde)) {
            if (is_numeric($ander) && abs((float)$waarde - (float)$ander) > 0.005) {
                $meldingen[] = sprintf('%s: php=%s js=%s', $plek, (string)$waarde, (string)$ander);
            }
            continue;
        }

        // Percentages in stijlattributen: PHP en JS printen een double met een
        // ander aantal cijfers. Dat is dezelfde breedte, dus numeriek vergelijken.
        if (preg_match('/^-?[\d.]+%$/', (string)$waarde) && preg_match('/^-?[\d.]+%$/', (string)$ander)) {
            if (abs((float)rtrim((string)$waarde, '%') - (float)rtrim((string)$ander, '%')) > 0.000001) {
                $meldingen[] = sprintf('%s: php=%s | js=%s', $plek, (string)$waarde, (string)$ander);
            }
            continue;
        }

        if ((string)$waarde !== (string)$ander) {
            $meldingen[] = sprintf('%s: php=%s | js=%s', $plek, (string)$waarde, (string)$ander);
        }
    }

    return $meldingen;
}

$fouten = 0;
$tolerantie = 0.005;

foreach ($phpUit as $i => $php) {
    $js = $jsUit[$i] ?? [];
    $naam = $gevallen[$i]['naam'];
    $verschillen = [];

    foreach ($php as $sleutel => $waarde) {
        if ($sleutel === 'vm') {
            continue;
        }
        $ander = $js[$sleutel] ?? null;
        if ($ander === null) {
            $verschillen[] = "$sleutel ontbreekt in JS";
            continue;
        }
        if (abs((float)$waarde - (float)$ander) > $tolerantie) {
            $verschillen[] = sprintf('%s: php=%.4f js=%.4f', $sleutel, (float)$waarde, (float)$ander);
        }
    }

    // Het viewmodel moet aan beide kanten dezelfde teksten opleveren.
    $phpVm = (new ViewModel(State::uitRequest($gevallen[$i]['in'])))->build();
    $jsVm = $js['vm'] ?? null;
    if (!is_array($jsVm)) {
        $verschillen[] = 'viewmodel ontbreekt in JS';
    } else {
        foreach (vergelijkViewmodel($phpVm, $jsVm) as $melding) {
            $verschillen[] = $melding;
        }
    }

    if ($verschillen === []) {
        printf("  ok   %s\n", $naam);
    } else {
        $fouten++;
        printf("  FOUT %s\n", $naam);
        foreach ($verschillen as $v) {
            printf("       %s\n", $v);
        }
    }
}

printf("\n%d gevallen, %d afwijkend\n", count($phpUit), $fouten);
exit($fouten === 0 ? 0 : 1);
