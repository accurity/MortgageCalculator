(function () {
    'use strict';

    var container = document.getElementById('leningdelen');
    var sjabloon = document.getElementById('leningdeel-sjabloon');
    var kostenContainer = document.getElementById('maandkosten');
    var kostenSjabloon = document.getElementById('kosten-sjabloon');

    // Namen van velden zijn geïndexeerd; na toevoegen of verwijderen hernummeren
    // we ze zodat PHP een aaneengesloten array ontvangt.
    function hernummer() {
        var delen = container.querySelectorAll('[data-leningdeel]');
        delen.forEach(function (deel, i) {
            deel.querySelector('.deelnummer').textContent = String(i + 1);
            deel.querySelectorAll('[name]').forEach(function (veld) {
                veld.name = veld.name.replace(/leningdeel\[[^\]]*\]/, 'leningdeel[' + i + ']');
            });
            deel.querySelectorAll('label[for]').forEach(function (label) {
                var nieuw = 'deel-' + i + '-' + label.htmlFor.split('-').pop();
                var invoer = deel.querySelector('#' + CSS.escape(label.htmlFor));
                label.htmlFor = nieuw;
                if (invoer) { invoer.id = nieuw; }
            });
        });
        var verwijderKnoppen = container.querySelectorAll('[data-verwijder]');
        verwijderKnoppen.forEach(function (knop) {
            knop.disabled = delen.length <= 1;
        });
    }

    function hernummerKosten() {
        kostenContainer.querySelectorAll('[data-kostenrij]').forEach(function (rij, i) {
            rij.querySelectorAll('[name]').forEach(function (veld) {
                veld.name = veld.name.replace(/maandkosten\[[^\]]*\]/, 'maandkosten[' + i + ']');
            });
        });
    }

    document.getElementById('voeg-leningdeel-toe').addEventListener('click', function () {
        var kopie = sjabloon.content.cloneNode(true);
        container.appendChild(kopie);
        hernummer();
        var laatste = container.querySelector('[data-leningdeel]:last-of-type');
        if (laatste) { laatste.querySelector('input[type="text"]').focus(); }
    });

    document.getElementById('voeg-kosten-toe').addEventListener('click', function () {
        kostenContainer.appendChild(kostenSjabloon.content.cloneNode(true));
        hernummerKosten();
    });

    document.addEventListener('click', function (e) {
        var knop = e.target.closest('[data-verwijder]');
        if (knop && container.contains(knop)) {
            knop.closest('[data-leningdeel]').remove();
            hernummer();
            return;
        }
        var kostenKnop = e.target.closest('[data-verwijder-kosten]');
        if (kostenKnop) {
            kostenKnop.closest('[data-kostenrij]').remove();
            hernummerKosten();
        }
    });

    hernummer();
    hernummerKosten();

    // Na een berekening meteen naar het resultaat springen.
    var resultaat = document.getElementById('resultaat');
    if (resultaat && !window.location.hash) {
        resultaat.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}());
