<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Hypotheek\Design\Calculator;
use Hypotheek\Design\Constants;
use Hypotheek\Design\State;
use Hypotheek\Design\Translations;
use Hypotheek\Design\ViewModel;

$invoer = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$state = State::uitRequest($invoer);

$actie = isset($invoer['do']) && is_string($invoer['do']) ? $invoer['do'] : '';
[$naam, $argument] = array_pad(explode(':', $actie, 2), 2, '');

pasActieToe($state, $naam, $argument);

if ($naam === 'csv' || $naam === 'pdf') {
    exporteerCsv($state);
    exit;
}

$vm = new ViewModel($state);
$v = $vm->build();
require __DIR__ . '/../templates/design/layout.php';

/**
 * Voert de actie van een knop uit. Dit zijn dezelfde handelingen als de h.*
 * handlers in het ontwerp; de JS-laag doet ze in de browser, hier gebeuren ze
 * op de server zodat alles ook zonder JavaScript werkt.
 */
function pasActieToe(State $s, string $naam, string $argument): void
{
    $stappen = $s->steps();

    switch ($naam) {
        case 'next':
            if ($s->step + 1 >= count($stappen)) {
                $s->view = 'calc';
                $s->mode = 'simple';
            } else {
                $s->step++;
            }
            break;

        case 'back':
            $s->step = max(0, $s->step - 1);
            break;

        case 'skipWizard':
            $s->view = 'calc';
            $s->mode = 'advanced';
            break;

        case 'restart':
            $s->view = 'wizard';
            $s->step = 0;
            $s->parts = null;
            $s->loan = null;
            break;

        case 'modeSimple':
            $s->mode = 'simple';
            break;

        case 'modeAdv':
            $s->mode = 'advanced';
            break;

        case 'langNl':
        case 'langEn':
            wisselTaal($s, $naam === 'langEn' ? 'en' : 'nl');
            break;

        case 'theme':
            $s->theme = $s->theme === 'dark' ? 'light' : 'dark';
            break;

        case 'openPay':
            $s->pay = true;
            break;

        case 'closePay':
            $s->pay = false;
            break;

        case 'buy':
            $s->plan = 'premium';
            $s->pay = false;
            break;

        case 'pathBuy':
        case 'pathRenew':
            $s->path = $naam === 'pathRenew' ? 'renew' : 'buy';
            $s->parts = null;
            $s->loan = null;
            $s->rate = null;
            break;

        case 'togglePre':
            $s->preTwentyThirteen = !$s->preTwentyThirteen;
            $s->parts = null;
            break;

        case 'fixed':
            $jaren = (int)$argument;
            if (in_array($jaren, Constants::FIXED_OPTIONS, true)) {
                $s->fixedY = $jaren;
                $s->rate = null;
                $s->parts = null;
                $s->lender = null;
            }
            break;

        case 'form':
            if (in_array($argument, Constants::FORMS, true)) {
                $s->form = $argument;
                $s->parts = null;
            }
            break;

        case 'curForm':
            if (in_array($argument, Constants::FORMS, true)) {
                $s->curForm = $argument;
            }
            break;

        case 'lender':
            kiesGeldverstrekker($s, (int)$argument);
            break;

        case 'cost.add':
            voegKostenpostToe($s, $argument);
            break;

        case 'addCost':
            $lijst = $s->kostenLijst();
            $lijst[] = ['id' => $s->nextId, 'label' => $s->lang === 'en' ? 'New item' : 'Nieuwe post', 'amount' => 0.0];
            $s->costs = $lijst;
            $s->nextId++;
            break;

        case 'cost.remove':
            $s->costs = array_values(array_filter(
                $s->kostenLijst(),
                static fn (array $post): bool => (string)$post['id'] !== $argument
            ));
            break;

        case 'addPart':
            voegLeningdeelToe($s);
            break;

        case 'part.remove':
            $delen = (new Calculator($s))->partsData();
            if (count($delen) > 1) {
                $s->parts = array_values(array_filter(
                    $delen,
                    static fn (array $deel): bool => (string)$deel['id'] !== $argument
                ));
            }
            break;

        case 'part.ded':
            $s->parts = array_map(
                static function (array $deel) use ($argument): array {
                    if ((string)$deel['id'] === $argument) {
                        $deel['deductible'] = empty($deel['deductible']);
                    }

                    return $deel;
                },
                (new Calculator($s))->partsData()
            );
            break;

        case 'part.form':
            [$id, $vorm] = array_pad(explode(':', $argument, 2), 2, '');
            if (in_array($vorm, Constants::FORMS, true)) {
                $s->parts = array_map(
                    static function (array $deel) use ($id, $vorm): array {
                        if ((string)$deel['id'] === $id) {
                            $deel['form'] = $vorm;
                        }

                        return $deel;
                    },
                    (new Calculator($s))->partsData()
                );
            }
            break;
    }
}

/** Bij een taalwissel schuiven de standaardnamen van de kostenposten mee. */
function wisselTaal(State $s, string $nieuw): void
{
    $oudeNamen = Translations::voor($s->lang)['costNames'];
    $nieuweNamen = Translations::voor($nieuw)['costNames'];

    $s->costs = array_map(
        static function (array $post) use ($oudeNamen, $nieuweNamen): array {
            foreach ($oudeNamen as $i => $rij) {
                if ($rij[0] === $post['label'] && isset($nieuweNamen[$i])) {
                    $post['label'] = $nieuweNamen[$i][0];
                    break;
                }
            }

            return $post;
        },
        $s->kostenLijst()
    );

    $s->lang = $nieuw;
}

function voegKostenpostToe(State $s, string $label): void
{
    $lijst = $s->kostenLijst();
    foreach ($lijst as $post) {
        if ($post['label'] === $label) {
            return;
        }
    }

    $bedrag = 0.0;
    foreach (Translations::voor($s->lang)['costNames'] as $rij) {
        if ($rij[0] === $label) {
            $bedrag = (float)$rij[1];
            break;
        }
    }

    $lijst[] = ['id' => $s->nextId, 'label' => $label, 'amount' => $bedrag];
    $s->costs = $lijst;
    $s->nextId++;
}

function voegLeningdeelToe(State $s): void
{
    $calc = new Calculator($s);
    $delen = $calc->partsData();

    // Gratis blijft bij twee delen; het ontwerp opent dan de paywall.
    if (!$s->isPremium() && count($delen) >= 2) {
        $s->pay = true;

        return;
    }

    $delen[] = [
        'id' => $s->nextId, 'form' => 'av', 'sum' => 50000.0, 'rate' => $calc->ioRate(),
        'term' => $calc->compute()['termY'], 'elapsed' => 0, 'deductible' => false, 'dedMonths' => 360,
    ];
    $s->parts = $delen;
    $s->nextId++;
}

function kiesGeldverstrekker(State $s, int $index): void
{
    /** @var list<array<string, mixed>> $rijen */
    $rijen = require __DIR__ . '/../data/lenders.php';
    if (!isset($rijen[$index]) || empty($rijen[$index]['active'])) {
        return;
    }

    $calc = new Calculator($s);
    $s->lender = $index;
    $s->rate = round(($calc->rate() + (float)$rijen[$index]['delta']) * 100) / 100;
    $s->parts = null;
}

/** Jaaroverzicht als CSV, met de kolommen uit het ontwerp. */
function exporteerCsv(State $s): void
{
    $vm = new ViewModel($s);
    $t = Translations::voor($s->lang);
    $C = (new Calculator($s))->compute();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="jaaroverzicht-hypotheek.csv"');

    $uit = fopen('php://output', 'wb');
    if ($uit === false) {
        return;
    }
    fwrite($uit, "\xEF\xBB\xBF"); // BOM, zodat Excel de accenten goed leest

    fputcsv($uit, [$t['colYear'], $t['colInterest'], $t['colPrincipal'], $t['colBalance'], $t['colMonthly']], ';', '"', '');
    foreach ($C['years'] as $jaar) {
        fputcsv($uit, [
            $jaar['y'],
            round($jaar['interest']),
            round($jaar['principal']),
            round($jaar['balance']),
            round($jaar['payment'] / max(1, $jaar['months'])),
        ], ';', '"', '');
    }
    fclose($uit);
}
