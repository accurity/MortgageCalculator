<?php

declare(strict_types=1);

namespace App\Services\Mortgage\Domain\Scraping;

use Illuminate\Support\Facades\Http;

/**
 * Simpele robots.txt-toetsing: geen robots.txt (of onbereikbaar) betekent
 * toegestaan; anders geldt de langste matchende Disallow/Allow-regel uit de
 * groep die het best bij onze User-Agent past (of "*" als er geen eigen
 * groep is), zoals gebruikelijk bij robots.txt.
 */
final class RobotsCheck
{
    public static function toegestaan(string $url, string $userAgent): bool
    {
        $delen = parse_url($url);
        if ($delen === false || empty($delen['scheme']) || empty($delen['host'])) {
            return false;
        }

        $robotsUrl = $delen['scheme'] . '://' . $delen['host'] . '/robots.txt';

        try {
            $response = Http::withUserAgent($userAgent)->timeout(10)->get($robotsUrl);
        } catch (\Throwable) {
            return true;
        }

        if (!$response->successful()) {
            return true;
        }

        $pad = ($delen['path'] ?? '/') . (isset($delen['query']) ? '?' . $delen['query'] : '');

        return self::magPad($response->body(), $userAgent, $pad === '' ? '/' : $pad);
    }

    private static function magPad(string $robotsTxt, string $userAgent, string $pad): bool
    {
        $groepen = self::parseer($robotsTxt);

        $eigen = null;
        foreach ($groepen as $agent => $regels) {
            if ($agent !== '*' && str_contains(strtolower($userAgent), $agent)) {
                $eigen = $regels;
                break;
            }
        }
        $regels = $eigen ?? ($groepen['*'] ?? []);

        $langsteMatch = null;
        $langsteLengte = -1;
        foreach ($regels as [$type, $prefix]) {
            if ($prefix !== '' && str_starts_with($pad, $prefix) && strlen($prefix) > $langsteLengte) {
                $langsteMatch = $type;
                $langsteLengte = strlen($prefix);
            }
        }

        return $langsteMatch !== 'disallow';
    }

    /** @return array<string, list<array{0: string, 1: string}>> agent (kleine letters) => [[type, pad-prefix]] */
    private static function parseer(string $robotsTxt): array
    {
        $groepen = [];
        $huidigeAgents = [];
        $inAgentBlok = false;

        foreach (preg_split('/\r\n|\r|\n/', $robotsTxt) as $regel) {
            $regel = trim((string)preg_replace('/#.*/', '', $regel));
            if ($regel === '' || !str_contains($regel, ':')) {
                continue;
            }
            [$sleutel, $waarde] = array_map('trim', explode(':', $regel, 2));
            $sleutel = strtolower($sleutel);

            if ($sleutel === 'user-agent') {
                if (!$inAgentBlok) {
                    $huidigeAgents = [];
                }
                $agent = strtolower($waarde);
                $huidigeAgents[] = $agent;
                $groepen[$agent] ??= [];
                $inAgentBlok = true;
                continue;
            }

            if (in_array($sleutel, ['disallow', 'allow'], true) && $huidigeAgents !== []) {
                foreach ($huidigeAgents as $agent) {
                    $groepen[$agent][] = [$sleutel, $waarde];
                }
                $inAgentBlok = false;
            }
        }

        return $groepen;
    }
}
