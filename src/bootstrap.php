<?php

declare(strict_types=1);

// Geen externe dependencies: alle klassen worden hier ingeladen.
foreach ([
    'Input.php',
    'Formatter.php',
    'LoanPart.php',
    'Amortization.php',
    'TaxRules.php',
    'TaxCalculator.php',
    'Mortgage.php',
    'CalculationRequest.php',
] as $bestand) {
    require_once __DIR__ . '/' . $bestand;
}
