<?php

declare(strict_types=1);

namespace App\Services\Mortgage;

/**
 * De teksten uit het Claude Design-ontwerp, NL en EN.
 *
 * De inhoud staat in translations.json, letterlijk overgenomen uit het ontwerp.
 * PHP rendert ermee en dezelfde JSON gaat mee naar de browser, zodat er maar
 * één lijst met teksten bestaat.
 */
final class Translations
{
    public const TALEN = ['nl', 'en'];

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $cache = null;

    /** @return array<string, array<string, mixed>> */
    public static function alle(): array
    {
        if (self::$cache === null) {
            $json = file_get_contents(__DIR__ . '/translations.json');
            if ($json === false) {
                throw new \RuntimeException('translations.json is niet leesbaar');
            }

            /** @var array<string, array<string, mixed>> $data */
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            self::$cache = $data;
        }

        return self::$cache;
    }

    /** @return array<string, mixed> */
    public static function voor(string $taal): array
    {
        $alle = self::alle();

        return $alle[$taal] ?? $alle['nl'];
    }

    public static function normaliseerTaal(?string $taal): string
    {
        return in_array($taal, self::TALEN, true) ? $taal : 'nl';
    }
}
