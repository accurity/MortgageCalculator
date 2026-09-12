<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Hypotheek\Amortization;
use Hypotheek\CalculationRequest;
use Hypotheek\LoanPart;
use Hypotheek\MonthlyCost;
use Hypotheek\Mortgage;
use Hypotheek\TaxCalculator;
use Hypotheek\TaxRules;

$geslaagd = 0;
$gefaald = 0;

function check(string $naam, bool $ok, string $detail = ''): void
{
    global $geslaagd, $gefaald;
    if ($ok) {
        $geslaagd++;
        echo "  ok   $naam\n";
    } else {
        $gefaald++;
        echo "  FAIL $naam" . ($detail !== '' ? " ($detail)" : '') . "\n";
    }
}

function bijna(float $a, float $b, float $marge = 0.01): bool
{
    return abs($a - $b) <= $marge;
}

echo "Annuïteit\n";
$amort = new Amortization(2026, 1);
$annuitair = new LoanPart('A', LoanPart::TYPE_ANNUITAIR, 250000, 3.9, 360);
$s = $amort->schedule($annuitair);
// Referentie: 250.000 tegen 3,9% over 360 maanden => 1.179,32 per maand.
check('maandtermijn', bijna($s->annuiteitBedrag, 1179.17, 0.01), (string)round($s->annuiteitBedrag, 2));
check('aantal termijnen', count($s->rows) === 360);
check('eindschuld nul', bijna($s->rows[359]->eindschuld, 0.0));
check('som aflossing = hoofdsom', bijna($s->totaleAflossing(), 250000.0, 0.05));
check('bruto maandlast constant', bijna($s->rows[0]->bruto(), $s->rows[200]->bruto(), 0.02));
check('rente daalt', $s->rows[0]->rente > $s->rows[359]->rente);

echo "Lineair\n";
$lineair = new LoanPart('L', LoanPart::TYPE_LINEAIR, 120000, 4.0, 240);
$s = $amort->schedule($lineair);
check('vaste aflossing', bijna($s->rows[0]->aflossing, 500.0) && bijna($s->rows[100]->aflossing, 500.0));
check('eerste rente', bijna($s->rows[0]->rente, 400.0));
check('eindschuld nul', bijna($s->rows[239]->eindschuld, 0.0));
check('dalende maandlast', $s->rows[0]->bruto() > $s->rows[239]->bruto());

echo "Aflossingsvrij\n";
$av = new LoanPart('AV', LoanPart::TYPE_AFLOSSINGSVRIJ, 100000, 4.2, 360);
$s = $amort->schedule($av);
check('geen aflossing', bijna($s->totaleAflossing(), 0.0));
check('vaste rente per maand', bijna($s->rows[0]->rente, 350.0));
check('slotsom = hoofdsom', bijna($s->slotsom, 100000.0));

echo "Renteloos leningdeel\n";
$nul = new LoanPart('N', LoanPart::TYPE_ANNUITAIR, 12000, 0.0, 12);
$s = $amort->schedule($nul);
check('0% annuïteit = hoofdsom/n', bijna($s->annuiteitBedrag, 1000.0));
check('0% eindschuld nul', bijna($s->rows[11]->eindschuld, 0.0));

echo "Reeds verstreken maanden\n";
$lopend = new LoanPart('Lopend', LoanPart::TYPE_LINEAIR, 240000, 3.0, 360, 120);
check('restschuld lineair', bijna($lopend->beginschuld(), 160000.0));
$lopendAnn = new LoanPart('LopendA', LoanPart::TYPE_ANNUITAIR, 250000, 3.9, 360, 0);
check('restschuld annuïtair bij 0 verstreken', bijna($lopendAnn->beginschuld(), 250000.0));
$halverwege = new LoanPart('Half', LoanPart::TYPE_ANNUITAIR, 250000, 3.9, 360, 180);
$schedHalf = $amort->schedule($halverwege);
check('annuïteit blijft gelijk na 180 mnd', bijna($schedHalf->annuiteitBedrag, 1179.17, 0.05),
    (string)round($schedHalf->annuiteitBedrag, 2));
check('resterende termijnen', count($schedHalf->rows) === 180);

echo "30-jaarsgrens renteaftrek\n";
$oud = new LoanPart('Oud', LoanPart::TYPE_AFLOSSINGSVRIJ, 100000, 4.0, 480, 350);
$s = $amort->schedule($oud);
check('eerste maanden aftrekbaar', $s->rows[0]->aftrekbareRente > 0.0);
check('na 360 maanden niet meer', bijna($s->rows[20]->aftrekbareRente, 0.0));

echo "Jaaroverzicht\n";
$mortgage = new Mortgage(
    parts: [
        new LoanPart('Annuïtair', LoanPart::TYPE_ANNUITAIR, 250000, 3.9, 360),
        new LoanPart('Aflossingsvrij', LoanPart::TYPE_AFLOSSINGSVRIJ, 100000, 4.1, 360, 0, true),
    ],
    startJaar: 2026,
    startMaand: 1,
    maandkosten: [new MonthlyCost('Verzekering', 25.0)],
    inkomenPerJaar: null,
);
$res = $mortgage->bereken();
check('30 jaarregels', count($res->jaren) === 30, (string)count($res->jaren));
check('eerste jaar 12 maanden', $res->jaren[0]->maanden === 12);
check('restschuld begin jaar 1', bijna($res->jaren[0]->restschuldBegin, 350000.0));
check('overige kosten per jaar', bijna($res->jaren[0]->overigeKosten, 300.0));
check('geen fiscale rijen zonder inkomen', $res->jaren[0]->fiscaal === null);
check('slotsom in laatste jaar', bijna($res->jaren[29]->totaleSlotsom(), 100000.0));
check('restschuld eind = 0', bijna($res->jaren[29]->restschuldEind, 0.0));
$somRente = array_sum(array_map(static fn ($j) => $j->rente, $res->jaren));
check('rente telt op', bijna($somRente, $res->totaleRente, 0.5));

echo "Belastingberekening\n";
$tax = new TaxCalculator(400000.0);
$r = $tax->bereken(2026, 70000.0, 12000.0);
check('eigenwoningforfait 0,35%', bijna($r->eigenwoningforfait, 1400.0));
check('aftrekpost = rente - ewf', bijna($r->aftrekpost, 10600.0));
check('voordeel positief', $r->voordeelJaar > 0);
check('effectief tarief ~ 37,56%', bijna($r->effectiefVoordeelTarief, 0.3756, 0.01),
    (string)round($r->effectiefVoordeelTarief, 4));

$regels = TaxRules::voorJaar(2026);
$hoog = $tax->bereken(2026, 120000.0, 20000.0);
$maxTarief = $regels->maxAftrektarief;
check('tariefsaanpassing begrenst voordeel',
    $hoog->effectiefVoordeelTarief <= $maxTarief + 0.0001,
    (string)round($hoog->effectiefVoordeelTarief, 4));

$laag = $tax->bereken(2026, 30000.0, 500.0);
check('Hillen bij lage rente', $laag->hillenAftrek > 0.0);

echo "Invoer parsen\n";
check('NL notatie', bijna(\Hypotheek\Input::toFloat('250.000,50'), 250000.50));
check('EN notatie', bijna(\Hypotheek\Input::toFloat('250000.50'), 250000.50));
check('euroteken', bijna(\Hypotheek\Input::toFloat('€ 1.234'), 1234.0));
check('procent', bijna(\Hypotheek\Input::toFloat('3,9%'), 3.9));
check('leeg', bijna(\Hypotheek\Input::toFloat(''), 0.0));

echo "Formulierverwerking\n";
$req = CalculationRequest::fromPost([
    'start_jaar' => '2026', 'start_maand' => '7',
    'leningdeel' => [
        ['naam' => '', 'type' => 'lineair', 'hoofdsom' => '200.000', 'rente' => '3,5',
         'looptijd_jaren' => '30', 'verstreken_maanden' => '0', 'aftrekbaar' => '1'],
        ['naam' => '', 'hoofdsom' => ''], // lege regel wordt genegeerd
    ],
    'maandkosten' => [['omschrijving' => 'VvE', 'bedrag' => '150']],
    'inkomen' => '',
]);
check('lege regel genegeerd', count($req->leningdelen) === 1);
check('validatie ok', $req->valideer(), implode('; ', $req->fouten));
check('geen fiscale berekening zonder inkomen', $req->inkomenPerJaar(2026, 2056) === null);
$req->inkomen = 60000.0;
$req->inkomenIndexatie = 2.0;
$reeks = $req->inkomenPerJaar(2026, 2027);
check('indexatie inkomen', bijna($reeks[2027], 61200.0, 0.01));

$ongeldig = CalculationRequest::fromPost([
    'leningdeel' => [['naam' => 'X', 'type' => 'annuitair', 'hoofdsom' => '0',
                      'rente' => '99', 'looptijd_jaren' => '0']],
]);
check('ongeldige invoer wordt afgekeurd', $ongeldig->valideer() === false);
check('drie foutmeldingen', count($ongeldig->fouten) >= 2, (string)count($ongeldig->fouten));

echo "\n$geslaagd geslaagd, $gefaald gefaald\n";
exit($gefaald === 0 ? 0 : 1);
