# Hypotheekcalculator

Webapplicatie in PHP (8.1+) om een hypotheek met **meerdere leningdelen** door te rekenen.
Geen frameworks, geen Composer-dependencies.

Het scherm is de pixel-voor-pixel nagebouwde versie van het Claude Design-ontwerp
(`Hypotheekcalculator.dc.html`): een wizard van acht stappen met twee paden (kopen of
oversluiten), een resultaatscherm met sliders, en NL/EN plus licht/donker.

## Wat het doet

* Onbeperkt aantal leningdelen, elk met een eigen:
  * aflossingsvorm: **annuïtair**, **lineair** of **aflossingsvrij**;
  * hoofdsom en rentepercentage;
  * looptijd — de periode waarin het leningdeel volledig is afgelost;
  * aantal reeds verstreken maanden (voor een hypotheek die al loopt);
  * vinkje of de rente fiscaal aftrekbaar is.
* **Jaaroverzicht** met rente, aflossing, restschuld en de bruto maandlast per jaar.
* **Maandkosten**: vrij in te vullen terugkerende posten (verzekering, VvE, gemeentelijke lasten)
  die als aparte woonlast worden meegeteld.
* **Maandlasten**: bruto per maand, en netto zodra er een inkomen bekend is.
* **Optioneel** het effect op de inkomstenbelasting: hypotheekrenteaftrek, eigenwoningforfait,
  Wet Hillen en de tariefsaanpassing. Vul je geen jaarinkomen in, dan blijft dat deel eenvoudig
  weg en werkt de rest van de berekening gewoon.
* Maandoverzicht per jaar (optioneel) en export van het jaaroverzicht naar CSV.
* **Twee paden**: een nieuwe hypotheek bij een koopsom, of een lopende hypotheek waarvan de
  rentevaste periode afloopt (met een vergelijking nu/straks).
* **Nederlands en Engels**, en een licht en donker thema.
* Een **premium-demo**: paywall, tarievenlijst per geldverstrekker en scenario's. Er wordt
  niets afgeschreven; de tarieven zijn voorbeelddata uit `data/lenders.php`.

## Starten

Lokaal, met de ingebouwde PHP-server:

```bash
php -S 0.0.0.0:8000 -t public
```

Daarna: <http://localhost:8000>

### Op een webserver

Zet de **documentroot op de map `public/`**. De mappen `src/` en `templates/` horen niet
rechtstreeks bereikbaar te zijn.

Apache-voorbeeld:

```apache
<VirtualHost *:80>
    ServerName hypotheek.example.nl
    DocumentRoot /var/www/hypotheek_calculator/public
    <Directory /var/www/hypotheek_calculator/public>
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
```

Nginx-voorbeeld:

```nginx
server {
    listen 80;
    server_name hypotheek.example.nl;
    root /var/www/hypotheek_calculator/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php$is_args$args; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Met Docker

```bash
docker compose up -d --build
```

Daarna: <http://localhost:8080>. Het image is `php:8.4-apache` met de docroot op `public/`.

De applicatie houdt geen state bij: geen database, geen sessies, geen cookies. Alle invoer
staat in één formulier dat naar dezelfde pagina post.

### Zonder JavaScript

Elke knop is een submit en elk veld gewone invoer, dus de hele wizard werkt zonder
JavaScript — alleen zonder live bijwerken. Met JavaScript onderschept `public/assets/js/app.js`
diezelfde elementen en rekent `calc.js` mee in de browser, zodat sliders direct doorwerken.

## Tests

```bash
php tests/run.php        # rekenkern
php tests/parity.php     # PHP-kant tegen JS-kant (heeft node nodig)
```

`run.php` dekt de aflossingsschema's (annuïtair/lineair/aflossingsvrij, ook bij 0% rente en bij
een reeds lopende hypotheek), de 30-jaarsgrens van de renteaftrek, de jaaraggregatie, de
fiscale berekening en het parsen van Nederlandse getalnotatie.

`parity.php` rekent 22 situaties door in PHP én in `public/assets/js/calc.js` en vergelijkt
zowel de bedragen als het complete viewmodel — elke tekst, elke kleur, elke lijstlengte. Die
twee moeten identiek zijn, anders is een van beide kanten gaan schuiven.

### Vergelijken met het ontwerp

```bash
tests/vergelijk-ontwerp.sh pad/naar/Hypotheekcalculator.dc.html
```

Stuurt het ontwerp én de draaiende site door dezelfde klikken (zelfde knoppen, zelfde
volgorde, animaties uitgezet) en telt de afwijkende pixels per scherm met `tests/pixeldiff.py`.
Zeventien schermen, van wizardstap tot paywall, in licht en donker: alle nul.

## Structuur

```
public/index.php        controller: state lezen, knopacties uitvoeren, CSV-export, view laden
public/assets/css/      design tokens en @font-face uit het ontwerp
public/assets/fonts/    Schibsted Grotesk en JetBrains Mono (variable, per subset)
public/assets/js/       calc.js (rekenkern), viewmodel.js (schermwaarden), app.js (bindingen)
src/LoanPart.php        één leningdeel + validatie + restschuld bij een lopende hypotheek
src/Amortization.php    aflossingsschema per maand (MonthRow, Schedule)
src/Mortgage.php        aggregatie per kalenderjaar (YearRow, MortgageResult)
src/TaxRules.php        fiscale parameters per jaar — HIER BIJWERKEN
src/TaxCalculator.php   eigenwoningforfait, Hillen, tariefsaanpassing, belastingvoordeel
src/CalculationRequest.php  formulierinvoer -> domeinobjecten
src/Design/State.php    de state uit het ontwerp, gelezen uit de request
src/Design/Calculator.php  afgeleiden en jaaraggregatie op basis van Amortization
src/Design/ViewModel.php   alle schermwaarden; de PHP-tegenhanger van viewmodel.js
src/Design/Constants.php   marktrentes en opslagen; fiscale cijfers komen uit TaxRules
src/Design/translations.json  NL/EN-teksten, letterlijk uit het ontwerp
src/Input.php           getalnotatie (zowel "1.234,56" als "1234.56")
src/Formatter.php       opmaak van bedragen en percentages
templates/design/       de nagebouwde schermen; inline styles komen uit het ontwerp
data/lenders.php        geldverstrekkers voor het tarievenblok (voorbeelddata)
tests/run.php           testsuite zonder dependencies
tests/parity.php        PHP tegen JS
tests/pixeldiff.py      pixelvergelijking zonder Pillow of ImageMagick
Dockerfile              php:8.4-apache met docroot op public/
docker-compose.yml      draait de app op poort 8080
```

## Rekenmethode

* Maandrente = jaarrente / 12 (nominaal, zoals gebruikelijk bij Nederlandse hypotheken).
* Annuïteit: `A = P · i / (1 − (1+i)^−n)`; bij 0% rente `A = P / n`.
* Lineair: vaste aflossing `P / n`, rente over de actuele restschuld.
* Aflossingsvrij: alleen rente; de hoofdsom wordt aan het einde van de looptijd als
  **slotsom** getoond en moet dan afgelost of overgesloten worden.
* Bij reeds verstreken maanden wordt de restschuld bepaald als contante waarde van de
  resterende termijnen (annuïtair) respectievelijk lineair afgeboekt.
* Renteaftrek geldt maximaal 30 jaar (360 maanden) vanaf het ontstaan van de schuld.

## Fiscale aannames en beperkingen

De tarieven staan als gewone PHP-array in `src/TaxRules.php`, per jaar. **Controleer ze bij
de Belastingdienst voordat je op de uitkomst vertrouwt.** Voor jaren die niet in de tabel
staan wordt het laatst bekende jaar doorgetrokken; het overzicht meldt dat.

Bewust buiten beschouwing gelaten, om de berekening navolgbaar te houden:

* heffingskortingen en de afbouw van de arbeidskorting;
* fiscaal partnerschap en de verdeling van de aftrek tussen partners;
* eenmalig aftrekbare kosten (afsluitprovisie, taxatie, notaris bij de hypotheekakte);
* inkomen uit andere bronnen dan box 1, en box 3;
* rentemiddeling, boeterente en het einde van de rentevaste periode.

Dit is een indicatieve berekening en geen financieel advies.
