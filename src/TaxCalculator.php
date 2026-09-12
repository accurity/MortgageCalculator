<?php

declare(strict_types=1);

namespace Hypotheek;

/**
 * Uitkomst van de fiscale berekening voor één kalenderjaar.
 */
final class TaxYearResult
{
    public function __construct(
        public readonly int $jaar,
        public readonly float $inkomen,
        public readonly float $aftrekbareRente,
        public readonly float $eigenwoningforfait,
        public readonly float $hillenAftrek,
        /** Negatief saldo = aftrekpost op het inkomen. */
        public readonly float $saldoEigenWoning,
        public readonly float $aftrekpost,
        public readonly float $marginaalTarief,
        public readonly float $effectiefVoordeelTarief,
        public readonly float $belastingZonder,
        public readonly float $belastingMet,
        public readonly float $voordeelJaar,
        public readonly bool $tarievenZijnSchatting,
    ) {
    }

    public function voordeelPerMaand(): float
    {
        return $this->voordeelJaar / 12;
    }
}

/**
 * Berekent het belastingvoordeel van de eigen woning in box 1.
 *
 * Vereenvoudigingen (bewust, om de berekening navolgbaar te houden):
 *  - geen heffingskortingen en geen afbouw van de arbeidskorting;
 *  - geen fiscale partner / verdeling van de aftrek;
 *  - geen aftrek van eenmalige kosten zoals afsluitprovisie of notaris.
 */
final class TaxCalculator
{
    public function __construct(
        private readonly float $wozWaarde = 0.0,
    ) {
    }

    public function bereken(int $jaar, float $inkomen, float $aftrekbareRente): TaxYearResult
    {
        $regels = TaxRules::voorJaar($jaar);

        $ewf = $this->eigenwoningforfait($regels);
        $saldo = $ewf - $aftrekbareRente;

        // Wet Hillen: is de rente lager dan het eigenwoningforfait, dan wordt het
        // positieve saldo (deels) weggestreept. Het aandeel bouwt jaarlijks af.
        $hillen = $saldo > 0 ? $saldo * $regels->hillenAandeel : 0.0;
        $belastbaarSaldo = $saldo - $hillen;

        $aftrekpost = max(0.0, -$belastbaarSaldo);
        $bijtelling = max(0.0, $belastbaarSaldo);

        // Zonder eigen woning betaal je belasting over het kale inkomen; met eigen
        // woning gaat de aftrekpost eraf of komt de bijtelling erbij.
        $inkomenMet = $inkomen + $bijtelling - $aftrekpost;

        $belastingZonder = $regels->belasting($inkomen);
        $belastingMet    = $regels->belasting($inkomenMet);

        $voordeel = $belastingZonder - $belastingMet;

        // Tariefsaanpassing: het deel van de aftrek dat boven de grens van de
        // hoogste schijf valt, levert maximaal het aangepaste tarief op in plaats
        // van het toptarief.
        if ($aftrekpost > 0.0) {
            $grens = $regels->topschijfGrens();
            $deelInTopschijf = max(0.0, $inkomen - max($grens, $inkomenMet));
            $correctie = $deelInTopschijf * ($regels->topTarief() - $regels->maxAftrektarief);
            $voordeel -= $correctie;
        }

        $effectief = $aftrekpost > 0.0 ? $voordeel / $aftrekpost : 0.0;

        return new TaxYearResult(
            jaar: $jaar,
            inkomen: $inkomen,
            aftrekbareRente: $aftrekbareRente,
            eigenwoningforfait: $ewf,
            hillenAftrek: $hillen,
            saldoEigenWoning: $belastbaarSaldo,
            aftrekpost: $aftrekpost,
            marginaalTarief: $regels->marginaalTarief($inkomen),
            effectiefVoordeelTarief: $effectief,
            belastingZonder: $belastingZonder,
            belastingMet: $belastingMet,
            voordeelJaar: $voordeel,
            tarievenZijnSchatting: $regels->isSchatting,
        );
    }

    private function eigenwoningforfait(TaxRules $regels): float
    {
        if ($this->wozWaarde <= 0.0) {
            return 0.0;
        }
        if ($this->wozWaarde <= $regels->ewfGrens) {
            return $this->wozWaarde * $regels->ewfPercentage;
        }

        // Boven de grens geldt een vast bedrag plus een hoog percentage over het meerdere.
        return $regels->ewfGrens * $regels->ewfPercentage
            + ($this->wozWaarde - $regels->ewfGrens) * 0.0235;
    }
}
