<?php

declare(strict_types=1);

namespace Hypotheek;

final class Formatter
{
    public static function euro(float $bedrag, int $decimalen = 0): string
    {
        return '€ ' . number_format($bedrag, $decimalen, ',', '.');
    }

    public static function procent(float $fractie, int $decimalen = 2): string
    {
        return number_format($fractie * 100, $decimalen, ',', '.') . '%';
    }

    public static function getal(float $waarde, int $decimalen = 2): string
    {
        return number_format($waarde, $decimalen, ',', '.');
    }

    public static function h(?string $waarde): string
    {
        return htmlspecialchars((string)$waarde, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function maandnaam(int $maand): string
    {
        $namen = [
            1 => 'januari', 'februari', 'maart', 'april', 'mei', 'juni',
            'juli', 'augustus', 'september', 'oktober', 'november', 'december',
        ];

        return $namen[$maand] ?? (string)$maand;
    }
}
