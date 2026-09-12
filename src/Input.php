<?php

declare(strict_types=1);

namespace Hypotheek;

/**
 * Kleine helpers voor het normaliseren van formulierinvoer.
 */
final class Input
{
    /**
     * Accepteert zowel "1.234,56" (NL) als "1234.56" (EN) notatie.
     */
    public static function toFloat(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float)$value;
        }
        $s = trim((string)$value);
        if ($s === '') {
            return 0.0;
        }
        $s = str_replace(['€', ' ', "\xc2\xa0", '%'], '', $s);

        $laatsteKomma = strrpos($s, ',');
        $laatstePunt  = strrpos($s, '.');

        if ($laatsteKomma !== false && $laatstePunt !== false) {
            // Het laatst voorkomende teken is het decimaalteken.
            if ($laatsteKomma > $laatstePunt) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($laatsteKomma !== false) {
            // Meerdere komma-groepen van drie cijfers: duizendtalscheiding.
            $s = (substr_count($s, ',') > 1 && preg_match('/^-?\d{1,3}(,\d{3})+$/', $s) === 1)
                ? str_replace(',', '', $s)
                : str_replace(',', '.', $s);
        } elseif ($laatstePunt !== false) {
            // "1.234" is in NL-notatie duizendtalscheiding, "1.23" een decimaal.
            if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $s) === 1) {
                $s = str_replace('.', '', $s);
            }
        }

        return is_numeric($s) ? (float)$s : 0.0;
    }

    public static function toInt(mixed $value, int $default = 0): int
    {
        $s = trim((string)$value);

        return $s === '' ? $default : (int)round(self::toFloat($s));
    }
}
