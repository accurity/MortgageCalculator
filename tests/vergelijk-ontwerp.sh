#!/bin/bash
# Vergelijkt de gebouwde site pixel voor pixel met het Claude Design-ontwerp.
#
#   tests/vergelijk-ontwerp.sh <pad-naar-ontwerp.html|referentiemap> [url]
#
# Het eerste argument is ofwel het uitgepakte artifact-bestand van het ontwerp
# (Hypotheekcalculator.dc.html uit Claude Design, niet in de repo omdat het
# eigendom is van het ontwerp), ofwel een map met eerder opgeslagen
# referentieschermen (tests/ontwerp/*.png, wel in de repo) - zo kan de
# vergelijking ook zonder het ontwerpbestand draaien. De url wijst naar een
# draaiende versie van deze site; standaard http://127.0.0.1:8399.
#
# Zet MAAK_REFERENTIES=tests/ontwerp om bij een .html-ontwerp meteen de
# referentieschermen (opnieuw) weg te schrijven, bijvoorbeeld na een update
# van het ontwerp.
#
# Beide kanten worden door hetzelfde harnas gestuurd: dezelfde knoppen, in
# dezelfde volgorde, met animaties uitgezet. Verschillen worden geteld met
# tests/pixeldiff.py; het label van Claude Design zelf blijft buiten beschouwing.
set -u

BRON=${1:?geef het pad naar het ontwerp-html-bestand of een referentiemap}
URL=${2:-http://127.0.0.1:8399}
W=${W:-1280}
H=${H:-900}

HIER=$(cd "$(dirname "$0")" && pwd)
WEBROOT="$HIER/../public"
CHROME=${CHROME:-$(command -v chromium || command -v google-chrome || echo "$HOME/.cache/ms-playwright/chromium-1223/chrome-linux64/chrome")}
WERKMAP=$(mktemp -d)

if [ -d "$BRON" ]; then
  REFDIR="$BRON"
  ONTWERP=""
else
  ONTWERP="$BRON"
  REFDIR=""
  cp "$HIER/ontwerp-harnas.html" "$(dirname "$ONTWERP")/_harnas.html"
fi
cp "$HIER/ontwerp-harnas.html" "$WEBROOT/_harnas.html"

opruimen () {
  rm -f "$WEBROOT/_harnas.html"
  [ -n "$ONTWERP" ] && rm -f "$(dirname "$ONTWERP")/_harnas.html"
  rm -rf "$WERKMAP"
}
trap opruimen EXIT

schot () { # bestand, basis-url, acties, extra-vlaggen
  "$CHROME" --headless=new --no-sandbox --disable-gpu --hide-scrollbars \
    --window-size=$W,$H --virtual-time-budget=25000 $4 \
    --screenshot="$1" "$2#$3" >/dev/null 2>&1
}

vergelijk () { # naam, acties
  local naam="$1"
  local acties="$2;wacht;wacht;wacht;wacht"
  local referentie="$WERKMAP/ontwerp.png"

  if [ -n "$REFDIR" ]; then
    referentie="$REFDIR/$naam.png"
  else
    schot "$WERKMAP/ontwerp.png" "file://$(dirname "$ONTWERP")/_harnas.html" \
          "$(basename "$ONTWERP")|$acties" "--allow-file-access-from-files"
    if [ -n "${MAAK_REFERENTIES:-}" ]; then
      mkdir -p "$MAAK_REFERENTIES"
      cp "$WERKMAP/ontwerp.png" "$MAAK_REFERENTIES/$naam.png"
    fi
  fi

  schot "$WERKMAP/site.png" "$URL/_harnas.html" "index.php|$acties" ""
  printf '%-24s ' "$naam"
  python3 "$HIER/pixeldiff.py" "$referentie" "$WERKMAP/site.png" \
    --negeer 1020,$((H-60)),$W,$H --uit "$WERKMAP/diff-$naam.png" | head -1
}

V=Verder
vergelijk stap1-start        ""
vergelijk stap2-bedrag       "$V"
vergelijk stap3-periode      "$V;$V"
vergelijk stap4-rente        "$V;$V;$V"
vergelijk stap5-vorm         "$V;$V;$V;$V"
vergelijk stap6-io           "$V;$V;$V;$V;$V"
vergelijk stap7-inkomen      "$V;$V;$V;$V;$V;$V"
vergelijk stap8-lasten       "$V;$V;$V;$V;$V;$V;$V"
vergelijk resultaat-simpel   "$V;$V;$V;$V;$V;$V;$V;Bekijk mijn woonlasten"
vergelijk resultaat-geavanceerd "Alles zelf invullen"
vergelijk oversluiten        "Mijn rentevaste periode loopt af;$V;$V;$V;$V;$V;$V;$V;Bekijk mijn woonlasten"
vergelijk donker             "Alles zelf invullen;@[title=\"Licht of donker\"]"
vergelijk engels             "Alles zelf invullen;EN"
vergelijk paywall            "Alles zelf invullen;Premium"
vergelijk premium            "Alles zelf invullen;Premium;Ontgrendelen — € 7,50 eenmalig"
vergelijk schuif-lening      "Alles zelf invullen;Overzicht;range1=500000"
vergelijk schuif-rente       "Alles zelf invullen;Overzicht;range2=520"

echo
echo "Maskers met de verschillen staan in $WERKMAP (blijven staan tot het einde van dit script)."
