<?php

declare(strict_types=1);

use Hypotheek\Formatter;
use Hypotheek\LoanPart;

/** @var \Hypotheek\CalculationRequest $request */

$maanden = [
    1 => 'januari', 'februari', 'maart', 'april', 'mei', 'juni',
    'juli', 'augustus', 'september', 'oktober', 'november', 'december',
];

/**
 * Rendert één leningdeel-rij. Wordt ook gebruikt als sjabloon voor nieuwe rijen,
 * met __INDEX__ als placeholder die JavaScript vervangt.
 *
 * @param array<string,mixed> $deel
 */
$renderLeningdeel = static function (string $index, array $deel) use ($maanden): void {
    $v = static fn (string $k, string $default = ''): string
        => Formatter::h((string)($deel[$k] ?? $default));
    ?>
    <fieldset class="leningdeel" data-leningdeel>
        <legend>Leningdeel <span class="deelnummer"></span></legend>
        <button type="button" class="verwijder" data-verwijder title="Leningdeel verwijderen">&times;</button>

        <div class="veld veld-breed">
            <label for="deel-<?= $index ?>-naam">Omschrijving</label>
            <input type="text" id="deel-<?= $index ?>-naam"
                   name="leningdeel[<?= $index ?>][naam]" value="<?= $v('naam') ?>"
                   placeholder="bijv. Annuïtair deel">
        </div>

        <div class="veld">
            <label for="deel-<?= $index ?>-type">Soort hypotheek</label>
            <select id="deel-<?= $index ?>-type" name="leningdeel[<?= $index ?>][type]">
                <?php foreach (LoanPart::TYPE_LABELS as $waarde => $label): ?>
                    <option value="<?= Formatter::h($waarde) ?>"
                        <?= ($deel['type'] ?? '') === $waarde ? 'selected' : '' ?>>
                        <?= Formatter::h($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="veld">
            <label for="deel-<?= $index ?>-hoofdsom">Hoofdsom (€)</label>
            <input type="text" inputmode="decimal" id="deel-<?= $index ?>-hoofdsom"
                   name="leningdeel[<?= $index ?>][hoofdsom]" value="<?= $v('hoofdsom') ?>"
                   placeholder="250.000" required>
        </div>

        <div class="veld">
            <label for="deel-<?= $index ?>-rente">Rente (% per jaar)</label>
            <input type="text" inputmode="decimal" id="deel-<?= $index ?>-rente"
                   name="leningdeel[<?= $index ?>][rente]" value="<?= $v('rente') ?>"
                   placeholder="3,9" required>
        </div>

        <div class="veld">
            <label for="deel-<?= $index ?>-looptijd">Looptijd (jaren)</label>
            <input type="text" inputmode="decimal" id="deel-<?= $index ?>-looptijd"
                   name="leningdeel[<?= $index ?>][looptijd_jaren]" value="<?= $v('looptijd_jaren', '30') ?>">
            <small>Periode waarin het leningdeel volledig is afgelost.</small>
        </div>

        <div class="veld">
            <label for="deel-<?= $index ?>-verstreken">Al verstreken (maanden)</label>
            <input type="text" inputmode="numeric" id="deel-<?= $index ?>-verstreken"
                   name="leningdeel[<?= $index ?>][verstreken_maanden]" value="<?= $v('verstreken_maanden', '0') ?>">
            <small>0 bij een nieuwe hypotheek.</small>
        </div>

        <div class="veld veld-breed veld-check">
            <label>
                <input type="checkbox" name="leningdeel[<?= $index ?>][aftrekbaar]" value="1"
                    <?= !empty($deel['aftrekbaar']) ? 'checked' : '' ?>>
                Rente is fiscaal aftrekbaar
            </label>
            <small>Aflossingsvrije delen aangegaan na 2013 zijn meestal niet aftrekbaar.
                   De aftrek stopt hoe dan ook na 30 jaar.</small>
        </div>
    </fieldset>
    <?php
};
?>

<form method="post" action="" class="rekenformulier" id="rekenformulier">

    <section class="kaart">
        <h2>Leningdelen</h2>
        <div id="leningdelen">
            <?php foreach ($request->leningdelen as $i => $deel): ?>
                <?php $renderLeningdeel((string)$i, $deel); ?>
            <?php endforeach; ?>
        </div>
        <button type="button" id="voeg-leningdeel-toe" class="knop knop-secundair">+ Leningdeel toevoegen</button>
    </section>

    <section class="kaart">
        <h2>Start van de berekening</h2>
        <div class="raster">
            <div class="veld">
                <label for="start_maand">Startmaand</label>
                <select id="start_maand" name="start_maand">
                    <?php foreach ($maanden as $nr => $naam): ?>
                        <option value="<?= $nr ?>" <?= $request->startMaand === $nr ? 'selected' : '' ?>>
                            <?= Formatter::h($naam) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="veld">
                <label for="start_jaar">Startjaar</label>
                <input type="text" inputmode="numeric" id="start_jaar" name="start_jaar"
                       value="<?= Formatter::h((string)$request->startJaar) ?>">
            </div>
        </div>
    </section>

    <section class="kaart">
        <h2>Overige maandkosten <span class="optioneel">optioneel</span></h2>
        <p class="uitleg">Terugkerende woonlasten naast rente en aflossing. Deze tellen mee in de
           maandkosten, maar niet in de renteaftrek.</p>
        <div id="maandkosten">
            <?php foreach ($request->maandkosten as $i => $kost): ?>
                <div class="kostenrij" data-kostenrij>
                    <input type="text" name="maandkosten[<?= $i ?>][omschrijving]"
                           value="<?= Formatter::h((string)($kost['omschrijving'] ?? '')) ?>"
                           placeholder="Omschrijving" aria-label="Omschrijving">
                    <input type="text" inputmode="decimal" name="maandkosten[<?= $i ?>][bedrag]"
                           value="<?= Formatter::h((string)($kost['bedrag'] ?? '')) ?>"
                           placeholder="€ per maand" aria-label="Bedrag per maand">
                    <button type="button" class="verwijder" data-verwijder-kosten title="Regel verwijderen">&times;</button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="voeg-kosten-toe" class="knop knop-secundair">+ Kostenregel toevoegen</button>
    </section>

    <section class="kaart">
        <h2>Inkomen en woning <span class="optioneel">optioneel</span></h2>
        <p class="uitleg">Laat je deze velden leeg, dan rekent de calculator gewoon door - alleen het
           deel over de inkomstenbelasting blijft dan achterwege.</p>
        <div class="raster">
            <div class="veld">
                <label for="inkomen">Bruto jaarinkomen (€)</label>
                <input type="text" inputmode="decimal" id="inkomen" name="inkomen"
                       value="<?= Formatter::h($request->inkomen !== null ? (string)round($request->inkomen) : '') ?>"
                       placeholder="bijv. 70.000">
                <small>Box 1, vóór aftrek van de eigen woning.</small>
            </div>
            <div class="veld">
                <label for="inkomen_indexatie">Jaarlijkse inkomensstijging (%)</label>
                <input type="text" inputmode="decimal" id="inkomen_indexatie" name="inkomen_indexatie"
                       value="<?= Formatter::h(rtrim(rtrim(number_format($request->inkomenIndexatie, 2, ',', ''), '0'), ',')) ?>"
                       placeholder="0">
                <small>Leeg of 0 = gelijkblijvend inkomen.</small>
            </div>
            <div class="veld">
                <label for="woz">WOZ-waarde woning (€)</label>
                <input type="text" inputmode="decimal" id="woz" name="woz"
                       value="<?= Formatter::h($request->wozWaarde > 0 ? (string)round($request->wozWaarde) : '') ?>"
                       placeholder="bijv. 425.000">
                <small>Nodig voor het eigenwoningforfait.</small>
            </div>
        </div>
    </section>

    <section class="kaart kaart-acties">
        <label class="veld-check">
            <input type="checkbox" name="toon_maand_detail" value="1"
                <?= $request->toonMaandDetail ? 'checked' : '' ?>>
            Maandoverzicht per jaar tonen
        </label>
        <div class="knoppen">
            <button type="submit" class="knop knop-primair">Bereken</button>
            <button type="submit" name="export" value="csv" class="knop knop-secundair">Download CSV</button>
        </div>
    </section>

    <template id="leningdeel-sjabloon">
        <?php
        $renderLeningdeel('__INDEX__', [
            'type' => LoanPart::TYPE_ANNUITAIR,
            'looptijd_jaren' => '30',
            'verstreken_maanden' => '0',
            'aftrekbaar' => '1',
        ]);
        ?>
    </template>

    <template id="kosten-sjabloon">
        <div class="kostenrij" data-kostenrij>
            <input type="text" name="maandkosten[__INDEX__][omschrijving]" placeholder="Omschrijving" aria-label="Omschrijving">
            <input type="text" inputmode="decimal" name="maandkosten[__INDEX__][bedrag]" placeholder="€ per maand" aria-label="Bedrag per maand">
            <button type="button" class="verwijder" data-verwijder-kosten title="Regel verwijderen">&times;</button>
        </div>
    </template>
</form>
