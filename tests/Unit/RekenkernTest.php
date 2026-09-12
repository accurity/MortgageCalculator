<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Mortgage\Domain\Amortization;
use App\Services\Mortgage\Domain\CalculationRequest;
use App\Services\Mortgage\Domain\LoanPart;
use App\Services\Mortgage\Domain\MonthlyCost;
use App\Services\Mortgage\Domain\Mortgage;
use App\Services\Mortgage\Domain\TaxCalculator;
use App\Services\Mortgage\Domain\TaxRules;
use PHPUnit\Framework\TestCase;

/**
 * Poort van het oorspronkelijke tests/run.php: dezelfde gevallen, nu als
 * PHPUnit-assertions op de rekenkern (aflossingsschema's, 30-jaarsgrens,
 * fiscale berekening, getalnotatie).
 */
final class RekenkernTest extends TestCase
{
    private function bijna(float $a, float $b, float $marge = 0.01): bool
    {
        return abs($a - $b) <= $marge;
    }

    public function test_annuiteit(): void
    {
        $amort = new Amortization(2026, 1);
        $annuitair = new LoanPart('A', LoanPart::TYPE_ANNUITAIR, 250000, 3.9, 360);
        $s = $amort->schedule($annuitair);

        // Referentie: 250.000 tegen 3,9% over 360 maanden => 1.179,32 per maand.
        $this->assertTrue($this->bijna($s->annuiteitBedrag, 1179.17, 0.01), (string)round($s->annuiteitBedrag, 2));
        $this->assertCount(360, $s->rows);
        $this->assertTrue($this->bijna($s->rows[359]->eindschuld, 0.0));
        $this->assertTrue($this->bijna($s->totaleAflossing(), 250000.0, 0.05));
        $this->assertTrue($this->bijna($s->rows[0]->bruto(), $s->rows[200]->bruto(), 0.02));
        $this->assertGreaterThan($s->rows[359]->rente, $s->rows[0]->rente);
    }

    public function test_lineair(): void
    {
        $amort = new Amortization(2026, 1);
        $lineair = new LoanPart('L', LoanPart::TYPE_LINEAIR, 120000, 4.0, 240);
        $s = $amort->schedule($lineair);

        $this->assertTrue($this->bijna($s->rows[0]->aflossing, 500.0) && $this->bijna($s->rows[100]->aflossing, 500.0));
        $this->assertTrue($this->bijna($s->rows[0]->rente, 400.0));
        $this->assertTrue($this->bijna($s->rows[239]->eindschuld, 0.0));
        $this->assertGreaterThan($s->rows[239]->bruto(), $s->rows[0]->bruto());
    }

    public function test_aflossingsvrij(): void
    {
        $amort = new Amortization(2026, 1);
        $av = new LoanPart('AV', LoanPart::TYPE_AFLOSSINGSVRIJ, 100000, 4.2, 360);
        $s = $amort->schedule($av);

        $this->assertTrue($this->bijna($s->totaleAflossing(), 0.0));
        $this->assertTrue($this->bijna($s->rows[0]->rente, 350.0));
        $this->assertTrue($this->bijna($s->slotsom, 100000.0));
    }

    public function test_renteloos_leningdeel(): void
    {
        $amort = new Amortization(2026, 1);
        $nul = new LoanPart('N', LoanPart::TYPE_ANNUITAIR, 12000, 0.0, 12);
        $s = $amort->schedule($nul);

        $this->assertTrue($this->bijna($s->annuiteitBedrag, 1000.0));
        $this->assertTrue($this->bijna($s->rows[11]->eindschuld, 0.0));
    }

    public function test_reeds_verstreken_maanden(): void
    {
        $amort = new Amortization(2026, 1);

        $lopend = new LoanPart('Lopend', LoanPart::TYPE_LINEAIR, 240000, 3.0, 360, 120);
        $this->assertTrue($this->bijna($lopend->beginschuld(), 160000.0));

        $lopendAnn = new LoanPart('LopendA', LoanPart::TYPE_ANNUITAIR, 250000, 3.9, 360, 0);
        $this->assertTrue($this->bijna($lopendAnn->beginschuld(), 250000.0));

        $halverwege = new LoanPart('Half', LoanPart::TYPE_ANNUITAIR, 250000, 3.9, 360, 180);
        $schedHalf = $amort->schedule($halverwege);
        $this->assertTrue(
            $this->bijna($schedHalf->annuiteitBedrag, 1179.17, 0.05),
            (string)round($schedHalf->annuiteitBedrag, 2)
        );
        $this->assertCount(180, $schedHalf->rows);
    }

    public function test_dertig_jaarsgrens_renteaftrek(): void
    {
        $amort = new Amortization(2026, 1);
        $oud = new LoanPart('Oud', LoanPart::TYPE_AFLOSSINGSVRIJ, 100000, 4.0, 480, 350);
        $s = $amort->schedule($oud);

        $this->assertGreaterThan(0.0, $s->rows[0]->aftrekbareRente);
        $this->assertTrue($this->bijna($s->rows[20]->aftrekbareRente, 0.0));
    }

    public function test_jaaroverzicht(): void
    {
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

        $this->assertCount(30, $res->jaren, (string)count($res->jaren));
        $this->assertSame(12, $res->jaren[0]->maanden);
        $this->assertTrue($this->bijna($res->jaren[0]->restschuldBegin, 350000.0));
        $this->assertTrue($this->bijna($res->jaren[0]->overigeKosten, 300.0));
        $this->assertNull($res->jaren[0]->fiscaal);
        $this->assertTrue($this->bijna($res->jaren[29]->totaleSlotsom(), 100000.0));
        $this->assertTrue($this->bijna($res->jaren[29]->restschuldEind, 0.0));

        $somRente = array_sum(array_map(static fn ($j) => $j->rente, $res->jaren));
        $this->assertTrue($this->bijna($somRente, $res->totaleRente, 0.5));
    }

    public function test_belastingberekening(): void
    {
        $tax = new TaxCalculator(400000.0);
        $r = $tax->bereken(2026, 70000.0, 12000.0);

        $this->assertTrue($this->bijna($r->eigenwoningforfait, 1400.0));
        $this->assertTrue($this->bijna($r->aftrekpost, 10600.0));
        $this->assertGreaterThan(0, $r->voordeelJaar);
        $this->assertTrue(
            $this->bijna($r->effectiefVoordeelTarief, 0.3756, 0.01),
            (string)round($r->effectiefVoordeelTarief, 4)
        );

        $regels = TaxRules::voorJaar(2026);
        $hoog = $tax->bereken(2026, 120000.0, 20000.0);
        $maxTarief = $regels->maxAftrektarief;
        $this->assertLessThanOrEqual(
            $maxTarief + 0.0001,
            $hoog->effectiefVoordeelTarief,
            (string)round($hoog->effectiefVoordeelTarief, 4)
        );

        $laag = $tax->bereken(2026, 30000.0, 500.0);
        $this->assertGreaterThan(0.0, $laag->hillenAftrek);
    }

    public function test_invoer_parsen(): void
    {
        $this->assertTrue($this->bijna(\App\Services\Mortgage\Domain\Input::toFloat('250.000,50'), 250000.50));
        $this->assertTrue($this->bijna(\App\Services\Mortgage\Domain\Input::toFloat('250000.50'), 250000.50));
        $this->assertTrue($this->bijna(\App\Services\Mortgage\Domain\Input::toFloat('€ 1.234'), 1234.0));
        $this->assertTrue($this->bijna(\App\Services\Mortgage\Domain\Input::toFloat('3,9%'), 3.9));
        $this->assertTrue($this->bijna(\App\Services\Mortgage\Domain\Input::toFloat(''), 0.0));
    }

    public function test_formulierverwerking(): void
    {
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
        $this->assertCount(1, $req->leningdelen);
        $this->assertTrue($req->valideer(), implode('; ', $req->fouten));
        $this->assertNull($req->inkomenPerJaar(2026, 2056));

        $req->inkomen = 60000.0;
        $req->inkomenIndexatie = 2.0;
        $reeks = $req->inkomenPerJaar(2026, 2027);
        $this->assertTrue($this->bijna($reeks[2027], 61200.0, 0.01));

        $ongeldig = CalculationRequest::fromPost([
            'leningdeel' => [['naam' => 'X', 'type' => 'annuitair', 'hoofdsom' => '0',
                              'rente' => '99', 'looptijd_jaren' => '0']],
        ]);
        $this->assertFalse($ongeldig->valideer());
        $this->assertGreaterThanOrEqual(2, count($ongeldig->fouten), (string)count($ongeldig->fouten));
    }
}
