<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain\Scraping;

/** Uitkomst van één scrape- of dry-run-poging voor één verstrekker. */
final class ScrapeResult
{
    /**
     * @param list<array{fixed_period_id: int, periode_jaren: int, risk_class_id: int, klasse_naam: string, percentage: float}> $rijen
     */
    private function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $rijen,
    ) {
    }

    /** @param list<array{fixed_period_id: int, periode_jaren: int, risk_class_id: int, klasse_naam: string, percentage: float}> $rijen */
    public static function geslaagd(array $rijen): self
    {
        return new self(true, count($rijen) . ' tarieven gevonden.', $rijen);
    }

    public static function mislukt(string $melding): self
    {
        return new self(false, $melding, []);
    }
}
