<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain;

/**
 * Uitkomst van het inlezen van een tarieven-CSV: ofwel een lijst geldige
 * rijen, ofwel een lijst foutmeldingen. Nooit allebei gedeeltelijk - een CSV
 * met ook maar één ongeldige regel levert alleen fouten op, geen rijen.
 */
final class RateImportResult
{
    /**
     * @param list<array{lender_id: int, lender_naam: string, fixed_period_id: int, periode_jaren: int, risk_class_id: int, klasse_naam: string, percentage: float}> $rijen
     * @param list<string> $fouten
     */
    public function __construct(
        public readonly array $rijen,
        public readonly array $fouten,
    ) {
    }

    public function geldig(): bool
    {
        return $this->fouten === [];
    }
}
