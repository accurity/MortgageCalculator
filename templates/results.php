<?php

declare(strict_types=1);

use Hypotheek\Formatter as F;
use Hypotheek\LoanPart;

/** @var \Hypotheek\CalculationRequest $request */
/** @var \Hypotheek\MortgageResult $resultaat */

$eersteJaar = $resultaat->jaren[0] ?? null;
$maandkosten = $request->monthlyCosts();
$overigPerMaand = array_sum(array_map(static fn ($k) => $k->bedrag, $maandkosten));
?>

<section class="kaart resultaat" id="resultaat">
    <h2>Samenvatting</h2>

    <div class="tegels">
        <div class="tegel">
            <span class="tegel-label">Totale hoofdsom</span>
            <span class="tegel-waarde"><?= F::euro($resultaat->totaleHoofdsom) ?></span>
        </div>
        <div class="tegel">
            <span class="tegel-label">Bruto maandlast (1e maand)</span>
            <span class="tegel-waarde"><?= F::euro($resultaat->eersteMaandBruto(), 2) ?></span>
            <span class="tegel-sub">rente + aflossing</span>
        </div>
        <?php if ($overigPerMaand > 0): ?>
        <div class="tegel">
            <span class="tegel-label">Bruto woonlast (1e maand)</span>
            <span class="tegel-waarde"><?= F::euro($resultaat->eersteMaandBruto() + $overigPerMaand, 2) ?></span>
            <span class="tegel-sub">incl. <?= F::euro($overigPerMaand, 2) ?> overige kosten</span>
        </div>
        <?php endif; ?>
        <?php if ($resultaat->fiscaalBerekend && $eersteJaar !== null): ?>
        <div class="tegel tegel-accent">
            <span class="tegel-label">Netto maandlast (jaar <?= $eersteJaar->jaar ?>)</span>
            <span class="tegel-waarde"><?= F::euro($eersteJaar->nettoWoonlastPerMaand(), 2) ?></span>
            <span class="tegel-sub">na <?= F::euro($eersteJaar->belastingvoordeelJaar() / max(1, $eersteJaar->maanden), 2) ?> belastingvoordeel p/m</span>
        </div>
        <?php endif; ?>
        <div class="tegel">
            <span class="tegel-label">Totale rente over de looptijd</span>
            <span class="tegel-waarde"><?= F::euro($resultaat->totaleRente) ?></span>
        </div>
        <div class="tegel">
            <span class="tegel-label">Totale aflossing</span>
            <span class="tegel-waarde"><?= F::euro($resultaat->totaleAflossing) ?></span>
        </div>
        <?php if ($resultaat->totaleSlotsom > 0.01): ?>
        <div class="tegel tegel-waarschuwing">
            <span class="tegel-label">Restschuld aan het einde</span>
            <span class="tegel-waarde"><?= F::euro($resultaat->totaleSlotsom) ?></span>
            <span class="tegel-sub">aflossingsvrij: in één keer opeisbaar</span>
        </div>
        <?php endif; ?>
        <?php if ($resultaat->fiscaalBerekend): ?>
        <div class="tegel">
            <span class="tegel-label">Totaal belastingvoordeel</span>
            <span class="tegel-waarde"><?= F::euro($resultaat->totaalBelastingvoordeel) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($resultaat->fiscaalBerekend && $resultaat->tarievenBevattenSchatting): ?>
        <p class="melding melding-let-op">Voor een deel van de jaren zijn de tarieven van het laatst
        bekende belastingjaar doorgetrokken. Behandel die jaren als indicatie.</p>
    <?php endif; ?>
    <?php if (!$resultaat->fiscaalBerekend): ?>
        <p class="melding melding-info">Geen jaarinkomen ingevuld, dus de berekening van de
        hypotheekrenteaftrek is overgeslagen. De rest van het overzicht klopt gewoon.</p>
    <?php endif; ?>
</section>

<section class="kaart">
    <h2>Per leningdeel</h2>
    <div class="tabel-scroll">
    <table>
        <thead>
        <tr>
            <th>Leningdeel</th><th>Soort</th><th class="num">Hoofdsom</th><th class="num">Rente</th>
            <th class="num">Looptijd</th><th class="num">1e maandlast</th>
            <th class="num">Totale rente</th><th class="num">Restschuld einde</th><th>Aftrekbaar</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($resultaat->schedules as $schedule):
            $part = $schedule->part;
            $eerste = $schedule->rows[0] ?? null; ?>
            <tr>
                <td><?= F::h($part->naam) ?></td>
                <td><?= F::h($part->typeLabel()) ?></td>
                <td class="num"><?= F::euro($part->beginschuld()) ?></td>
                <td class="num"><?= F::getal($part->rentePercentage, 2) ?>%</td>
                <td class="num"><?= F::getal($part->resterendeMaanden() / 12, 1) ?> jr</td>
                <td class="num"><?= $eerste ? F::euro($eerste->bruto(), 2) : '-' ?></td>
                <td class="num"><?= F::euro($schedule->totaleRente()) ?></td>
                <td class="num<?= $schedule->slotsom > 0.01 ? ' let-op' : '' ?>"><?= F::euro($schedule->slotsom) ?></td>
                <td><?= $part->renteAftrekbaar ? 'ja' : 'nee' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php if ($maandkosten !== []): ?>
        <p class="uitleg">Overige maandkosten:
            <?= F::h(implode(', ', array_map(
                static fn ($k) => $k->omschrijving . ' ' . F::euro($k->bedrag, 2),
                $maandkosten
            ))) ?> - samen <?= F::euro($overigPerMaand, 2) ?> per maand.</p>
    <?php endif; ?>
</section>

<section class="kaart">
    <h2>Jaaroverzicht</h2>
    <div class="tabel-scroll">
    <table class="jaartabel">
        <thead>
        <tr>
            <th>Jaar</th>
            <th class="num">Mnd</th>
            <th class="num">Schuld begin</th>
            <th class="num">Rente</th>
            <th class="num">Aflossing</th>
            <th class="num">Schuld eind</th>
            <th class="num">Bruto p/m</th>
            <?php if ($overigPerMaand > 0): ?><th class="num">Overig p/m</th><?php endif; ?>
            <?php if ($resultaat->fiscaalBerekend): ?>
                <th class="num">Aftrekbare rente</th>
                <th class="num">Voordeel p/jr</th>
                <th class="num">Netto p/m</th>
            <?php endif; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($resultaat->jaren as $jaar): ?>
            <tr>
                <td><?= $jaar->jaar ?></td>
                <td class="num"><?= $jaar->maanden ?></td>
                <td class="num"><?= F::euro($jaar->restschuldBegin) ?></td>
                <td class="num"><?= F::euro($jaar->rente) ?></td>
                <td class="num"><?= F::euro($jaar->aflossing) ?></td>
                <td class="num"><?= F::euro($jaar->restschuldEind) ?></td>
                <td class="num"><?= F::euro($jaar->brutoMaandlast(), 2) ?></td>
                <?php if ($overigPerMaand > 0): ?>
                    <td class="num"><?= F::euro($jaar->overigeMaandkosten(), 2) ?></td>
                <?php endif; ?>
                <?php if ($resultaat->fiscaalBerekend): ?>
                    <td class="num"><?= F::euro($jaar->aftrekbareRente) ?></td>
                    <td class="num"><?= F::euro($jaar->belastingvoordeelJaar()) ?></td>
                    <td class="num sterk"><?= F::euro($jaar->nettoMaandlast(), 2) ?></td>
                <?php endif; ?>
            </tr>
            <?php if ($jaar->slotsommen !== []): ?>
                <tr class="rij-slotsom">
                    <td colspan="<?= 7 + ($overigPerMaand > 0 ? 1 : 0) + ($resultaat->fiscaalBerekend ? 3 : 0) ?>">
                        Einde looptijd aflossingsvrij:
                        <?php foreach ($jaar->slotsommen as $naam => $bedrag): ?>
                            <strong><?= F::h((string)$naam) ?></strong> <?= F::euro($bedrag) ?>
                        <?php endforeach; ?>
                        moet in één keer worden afgelost of overgesloten.
                    </td>
                </tr>
            <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
        <tr>
            <th>Totaal</th>
            <th class="num"><?= $resultaat->looptijdMaanden() ?></th>
            <th></th>
            <th class="num"><?= F::euro($resultaat->totaleRente) ?></th>
            <th class="num"><?= F::euro($resultaat->totaleAflossing) ?></th>
            <th></th>
            <th></th>
            <?php if ($overigPerMaand > 0): ?><th class="num"><?= F::euro($resultaat->totaleOverigeKosten) ?></th><?php endif; ?>
            <?php if ($resultaat->fiscaalBerekend): ?>
                <th></th>
                <th class="num"><?= F::euro($resultaat->totaalBelastingvoordeel) ?></th>
                <th></th>
            <?php endif; ?>
        </tr>
        </tfoot>
    </table>
    </div>
</section>

<?php if ($resultaat->fiscaalBerekend): ?>
<section class="kaart">
    <h2>Inkomstenbelasting - eigen woning</h2>
    <p class="uitleg">Het belastingvoordeel is het verschil tussen de belasting zonder en met de
       eigenwoningaftrek, begrensd door het maximale aftrektarief. Heffingskortingen, fiscaal
       partnerschap en eenmalige aftrekposten zijn buiten beschouwing gelaten.</p>
    <div class="tabel-scroll">
    <table>
        <thead>
        <tr>
            <th>Jaar</th><th class="num">Inkomen</th><th class="num">Aftrekbare rente</th>
            <th class="num">Eigenwoningforfait</th><th class="num">Hillen</th>
            <th class="num">Aftrekpost</th><th class="num">Marginaal tarief</th>
            <th class="num">Effectief voordeel</th><th class="num">Voordeel per jaar</th>
            <th class="num">Per maand</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($resultaat->jaren as $jaar):
            $f = $jaar->fiscaal;
            if ($f === null) { continue; } ?>
            <tr>
                <td><?= $jaar->jaar ?></td>
                <td class="num"><?= F::euro($f->inkomen) ?></td>
                <td class="num"><?= F::euro($f->aftrekbareRente) ?></td>
                <td class="num"><?= F::euro($f->eigenwoningforfait) ?></td>
                <td class="num"><?= F::euro($f->hillenAftrek) ?></td>
                <td class="num"><?= F::euro($f->aftrekpost) ?></td>
                <td class="num"><?= F::procent($f->marginaalTarief) ?></td>
                <td class="num"><?= F::procent($f->effectiefVoordeelTarief) ?></td>
                <td class="num"><?= F::euro($f->voordeelJaar) ?></td>
                <td class="num sterk"><?= F::euro($f->voordeelPerMaand(), 2) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</section>
<?php endif; ?>

<?php if ($request->toonMaandDetail): ?>
<section class="kaart">
    <h2>Maandoverzicht per jaar</h2>
    <?php
    // Tel alle leningdelen per maand bij elkaar op.
    $perMaand = [];
    foreach ($resultaat->schedules as $schedule) {
        foreach ($schedule->rows as $row) {
            $key = $row->index;
            if (!isset($perMaand[$key])) {
                $perMaand[$key] = [
                    'jaar' => $row->jaar, 'maand' => $row->maand,
                    'begin' => 0.0, 'rente' => 0.0, 'aflossing' => 0.0, 'eind' => 0.0,
                ];
            }
            $perMaand[$key]['begin'] += $row->beginschuld;
            $perMaand[$key]['rente'] += $row->rente;
            $perMaand[$key]['aflossing'] += $row->aflossing;
            $perMaand[$key]['eind'] += $row->eindschuld;
        }
    }
    ksort($perMaand);
    $gegroepeerd = [];
    foreach ($perMaand as $rij) {
        $gegroepeerd[$rij['jaar']][] = $rij;
    }
    ?>
    <?php foreach ($gegroepeerd as $jaarNr => $rijen): ?>
        <details>
            <summary><?= (int)$jaarNr ?> <span class="tegel-sub">(<?= count($rijen) ?> maanden)</span></summary>
            <div class="tabel-scroll">
            <table class="compact">
                <thead>
                <tr><th>Maand</th><th class="num">Schuld begin</th><th class="num">Rente</th>
                    <th class="num">Aflossing</th><th class="num">Bruto maandlast</th>
                    <th class="num">Schuld eind</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rijen as $rij): ?>
                    <tr>
                        <td><?= F::h(F::maandnaam((int)$rij['maand'])) ?></td>
                        <td class="num"><?= F::euro($rij['begin'], 2) ?></td>
                        <td class="num"><?= F::euro($rij['rente'], 2) ?></td>
                        <td class="num"><?= F::euro($rij['aflossing'], 2) ?></td>
                        <td class="num sterk"><?= F::euro($rij['rente'] + $rij['aflossing'], 2) ?></td>
                        <td class="num"><?= F::euro($rij['eind'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </details>
    <?php endforeach; ?>
</section>
<?php endif; ?>
