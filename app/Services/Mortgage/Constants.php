<?php

declare(strict_types=1);

namespace App\Services\Mortgage;

use App\Services\Mortgage\Domain\RateRepository;
use App\Services\Mortgage\Domain\TaxRules;

/**
 * De constanten uit het Claude Design-ontwerp.
 *
 * De opslag op een aflossingsvrij deel en de premiumprijs komen letterlijk uit
 * het ontwerp. De fiscale getallen komen NIET uit het ontwerp: dat rekent met
 * de schijven van 2025 onder het label 2026. TaxRules is de fiscale autoriteit
 * in dit project, dus die vullen we hier vanuit. De marktrente komt uit
 * RateRepository (tabel `rates`, met een terugval uit `settings`), niet meer
 * uit een vaste MARKET-constante.
 */
final class Constants
{
    /** Belastingjaar waarmee de calculator rekent. */
    public const TAX_YEAR = 2026;

    /** Renteopslag in procentpunten op een aflossingsvrij leningdeel. */
    public const IO_SURCHARGE = 0.20;

    /** Een aflossingsvrij deel mag hooguit dit aandeel van de woningwaarde zijn. */
    public const IO_MAX_SHARE = 0.5;

    /** Eenmalige prijs van premium, als weergavestring. */
    public const PRICE = '7,50';

    /** Hypotheekvormen in de volgorde van het ontwerp. */
    public const FORMS = ['ann', 'lin', 'av'];

    /** Ontwerpcode => LoanPart-type. */
    public const FORM_TYPES = [
        'ann' => \App\Services\Mortgage\Domain\LoanPart::TYPE_ANNUITAIR,
        'lin' => \App\Services\Mortgage\Domain\LoanPart::TYPE_LINEAIR,
        'av'  => \App\Services\Mortgage\Domain\LoanPart::TYPE_AFLOSSINGSVRIJ,
    ];

    /** Rentevaste periodes die als knop getoond worden: de actieve periodes uit de admin. */
    public static function fixedOptions(): array
    {
        return RateRepository::periodes();
    }

    /** Eigenwoningforfait als fractie van de WOZ-waarde. */
    public static function ewfRate(): float
    {
        return TaxRules::voorJaar(self::TAX_YEAR)->ewfPercentage;
    }

    /** Maximaal tarief waartegen de aftrek verrekend wordt, in procenten. */
    public static function capRate(): float
    {
        return TaxRules::voorJaar(self::TAX_YEAR)->maxAftrektarief * 100;
    }

    /** Aandeel van de Hillen-aftrek dat dit jaar nog geldt (0..1). */
    public static function hillen(): float
    {
        return TaxRules::voorJaar(self::TAX_YEAR)->hillenAandeel;
    }

    /**
     * Schijven als [grens|null, tarief in procenten], oplopend.
     *
     * @return list<array{0: float|null, 1: float}>
     */
    public static function brackets(): array
    {
        $uit = [];
        foreach (TaxRules::voorJaar(self::TAX_YEAR)->schijven as $schijf) {
            $uit[] = [$schijf['tot'], $schijf['tarief'] * 100];
        }

        return $uit;
    }

    /**
     * Alles wat de browser nodig heeft om dezelfde som te maken.
     *
     * @return array<string, mixed>
     */
    public static function forJs(): array
    {
        return [
            'TAX_YEAR'     => self::TAX_YEAR,
            'EWF_RATE'     => self::ewfRate(),
            'CAP_RATE'     => self::capRate(),
            'HILLEN'       => self::hillen(),
            'BRACKETS'     => self::brackets(),
            'FIXED_OPTIONS' => self::fixedOptions(),
            'RATE_TABLE'   => RateRepository::tabelVoorJs(),
            'RISK_CLASSES' => RateRepository::klassenVoorJs(),
            'NHG_GRENS'    => RateRepository::nhgGrens(),
            'IO_SURCHARGE' => self::IO_SURCHARGE,
            'IO_MAX_SHARE' => self::IO_MAX_SHARE,
            'PRICE'        => self::PRICE,
        ];
    }
}
