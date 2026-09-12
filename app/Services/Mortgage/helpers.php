<?php

declare(strict_types=1);

/**
 * Escape-helper voor de templates.
 *
 * De templates zijn uit het ontwerp gegenereerd en roepen e() aan op elke
 * waarde die uit het viewmodel komt.
 */
if (!function_exists('e')) {
    function e(mixed $waarde): string
    {
        if (is_bool($waarde)) {
            $waarde = $waarde ? '1' : '';
        }

        return htmlspecialchars((string)$waarde, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
