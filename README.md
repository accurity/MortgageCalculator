# Hypotheekcalculator

Webapplicatie in PHP (8.1+) om een hypotheek met **meerdere leningdelen** door te rekenen.
Geen frameworks, geen Composer-dependencies.

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

De applicatie houdt geen state bij: geen database, geen sessies, geen cookies.
Alle invoer gaat via één POST naar dezelfde pagina.

## Tests

```bash
php tests/run.php
```

Dekt de aflossingsschema's (annuïtair/lineair/aflossingsvrij, ook bij 0% rente en bij een
reeds lopende hypotheek), de 30-jaarsgrens van de renteaftrek, de jaaraggregatie, de fiscale
berekening en het parsen van Nederlandse getalnotatie.

## Structuur

```
public/index.php        controller: invoer verwerken, CSV-export, view laden
public/assets/          stylesheet en javascript (rijen toevoegen/verwijderen)
src/LoanPart.php        één leningdeel + validatie + restschuld bij een lopende hypotheek
src/Amortization.php    aflossingsschema per maand (MonthRow, Schedule)
src/Mortgage.php        aggregatie per kalenderjaar (YearRow, MortgageResult)
src/TaxRules.php        fiscale parameters per jaar — HIER BIJWERKEN
src/TaxCalculator.php   eigenwoningforfait, Hillen, tariefsaanpassing, belastingvoordeel
src/CalculationRequest.php  formulierinvoer -> domeinobjecten
src/Input.php           getalnotatie (zowel "1.234,56" als "1234.56")
src/Formatter.php       opmaak van bedragen en percentages
templates/              layout, formulier en resultaat
tests/run.php           testsuite zonder dependencies
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
