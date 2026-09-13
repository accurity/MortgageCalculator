# Hypotheekcalculator

Laravel 13-applicatie om een hypotheek met **meerdere leningdelen** door te rekenen.
Gewone controllers en Blade-views, geen extra gedoe.

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

```bash
cp .env.example .env
composer install
php artisan key:generate
```

`.env.example` wijst naar MariaDB. Voor snel lokaal draaien zonder database-server kan
`DB_CONNECTION=sqlite` met een leeg `database/database.sqlite`-bestand ook:

```bash
sed -i 's/^DB_CONNECTION=mariadb/DB_CONNECTION=sqlite/' .env
touch database/database.sqlite
php artisan migrate
php artisan serve
```

Daarna: <http://localhost:8000>

### Met Docker (aanbevolen, gelijk aan productie)

```bash
docker compose up -d --build
docker compose exec hypotheek php artisan migrate --force
```

Daarna: <http://localhost:8080>. Dit start de app (php:8.4-apache) én een MariaDB-service;
lokaal wordt dus tegen dezelfde databasesoort ontwikkeld als op productie. De app-container
leest zijn database-instellingen uit `docker-compose.yml`, niet uit `.env` (dat blijft voor
losse, niet-gecontaineriseerde runs).

### Op een webserver

Zet de **documentroot op de map `public/`**, PHP 8.3 of hoger. De overige mappen (`app/`,
`resources/`, `routes/`, `config/`, `database/`) horen niet rechtstreeks bereikbaar te zijn.

### Zonder JavaScript

Elke knop is een submit en elk veld gewone invoer, dus de hele wizard werkt zonder
JavaScript — alleen zonder live bijwerken. Met JavaScript onderschept `public/assets/js/app.js`
diezelfde elementen en rekent `calc.js` mee in de browser, zodat sliders direct doorwerken.

## Tests

```bash
php artisan test         # rekenkern + de homepage-route (PHPUnit)
php tests/parity.php     # PHP-kant tegen JS-kant (heeft node nodig)
```

`tests/Unit/RekenkernTest.php` dekt de aflossingsschema's (annuïtair/lineair/aflossingsvrij,
ook bij 0% rente en bij een reeds lopende hypotheek), de 30-jaarsgrens van de renteaftrek, de
jaaraggregatie, de fiscale berekening en het parsen van Nederlandse getalnotatie.
`tests/Feature/CalculatorTest.php` toetst de route zelf.

`parity.php` rekent 22 situaties door in PHP én in `public/assets/js/calc.js` en vergelijkt
zowel de bedragen als het complete viewmodel — elke tekst, elke kleur, elke lijstlengte. Die
twee moeten identiek zijn, anders is een van beide kanten gaan schuiven.

### Vergelijken met het ontwerp

```bash
# Tegen de opgeslagen referentieschermen (geen ontwerpbestand nodig):
tests/vergelijk-ontwerp.sh tests/ontwerp

# Tegen het originele ontwerp, en meteen de referenties bijwerken:
MAAK_REFERENTIES=tests/ontwerp tests/vergelijk-ontwerp.sh pad/naar/Hypotheekcalculator.dc.html
```

Stuurt het ontwerp (of de opgeslagen schermen) én de draaiende site door dezelfde klikken
(zelfde knoppen, zelfde volgorde, animaties uitgezet) en telt de afwijkende pixels per scherm
met `tests/pixeldiff.py`. Zeventien schermen, van wizardstap tot paywall, in licht en donker:
alle nul. `tests/ontwerp/*.png` staat in de repo zodat dit zonder het ontwerpbestand kan.

## Structuur

```
app/Http/Controllers/CalculatorController.php  state lezen, knopacties uitvoeren, CSV-export
app/Http/Controllers/Admin/TaxYearController.php  CRUD voor de fiscale jaartarieven
app/Services/Mortgage/Domain/          rekenkern: LoanPart, Amortization, Mortgage, TaxRules,
                                        TaxCalculator, CalculationRequest, Input, Formatter
app/Services/Mortgage/State.php        de state uit het ontwerp, gelezen uit de request
app/Services/Mortgage/Calculator.php   afgeleiden en jaaraggregatie op basis van Amortization
app/Services/Mortgage/ViewModel.php    alle schermwaarden; de PHP-tegenhanger van viewmodel.js
app/Services/Mortgage/Constants.php    marktrentes en opslagen; fiscale cijfers komen uit TaxRules
app/Services/Mortgage/translations.json  NL/EN-teksten, letterlijk uit het ontwerp
app/Models/TaxYear.php                 fiscale jaartarieven (schijven, EWF, Wet Hillen), tabel tax_years
resources/views/design/                de nagebouwde schermen; inline styles uit het ontwerp
resources/views/admin/tax-years/       CRUD-schermen voor de belastingjaren
routes/web.php                         "/" -> CalculatorController, "/admin/..." -> beheer
public/assets/css/      design tokens en @font-face uit het ontwerp
public/assets/fonts/    Schibsted Grotesk en JetBrains Mono (variable, per subset)
public/assets/js/       calc.js (rekenkern), viewmodel.js (schermwaarden), app.js (bindingen)
data/lenders.php        geldverstrekkers voor het tarievenblok (voorbeelddata)
tests/Unit/, tests/Feature/  PHPUnit
tests/parity.php        PHP tegen JS
tests/pixeldiff.py      pixelvergelijking zonder Pillow of ImageMagick
tests/ontwerp/          opgeslagen referentieschermen van het ontwerp
Dockerfile              php:8.4-apache met docroot op public/, composer install in de image
docker-compose.yml      app op poort 8080 + een MariaDB-service
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

De tarieven staan per jaar in de `tax_years`-tabel, beheerbaar via `/admin/tax-years`
(`database/seeders/TaxYearSeeder.php` vult 2024-2026). **Controleer ze bij de Belastingdienst
voordat je op de uitkomst vertrouwt.** Voor jaren die niet in de tabel staan wordt het laatst
bekende jaar doorgetrokken en geldt de uitkomst als schatting.

Bewust buiten beschouwing gelaten, om de berekening navolgbaar te houden:

* heffingskortingen en de afbouw van de arbeidskorting;
* fiscaal partnerschap en de verdeling van de aftrek tussen partners;
* eenmalig aftrekbare kosten (afsluitprovisie, taxatie, notaris bij de hypotheekakte);
* inkomen uit andere bronnen dan box 1, en box 3;
* rentemiddeling, boeterente en het einde van de rentevaste periode.

Dit is een indicatieve berekening en geen financieel advies.
