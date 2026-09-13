<?php

declare(strict_types=1);

namespace Tests\Feature\Scraping;

use App\Services\Mortgage\Domain\Scraping\RobotsCheck;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class RobotsCheckTest extends TestCase
{
    private const UA = 'Mozilla/5.0 (compatible; AccurityRatesBot/1.0; +https://mortgagecalculator.accurity.nl)';

    public function test_zonder_robots_txt_is_alles_toegestaan(): void
    {
        Http::fake(['*/robots.txt' => Http::response('', 404)]);

        $this->assertTrue(RobotsCheck::toegestaan('https://voorbeeld.nl/tarieven', self::UA));
    }

    public function test_disallow_op_pad_blokkeert(): void
    {
        Http::fake(['*/robots.txt' => Http::response("User-agent: *\nDisallow: /tarieven\n")]);

        $this->assertFalse(RobotsCheck::toegestaan('https://voorbeeld.nl/tarieven', self::UA));
    }

    public function test_disallow_op_ander_pad_blokkeert_niet(): void
    {
        Http::fake(['*/robots.txt' => Http::response("User-agent: *\nDisallow: /prive\n")]);

        $this->assertTrue(RobotsCheck::toegestaan('https://voorbeeld.nl/tarieven', self::UA));
    }

    public function test_specifieke_agent_gaat_voor_sterretje(): void
    {
        Http::fake(['*/robots.txt' => Http::response(
            "User-agent: *\nDisallow:\n\nUser-agent: accurityratesbot\nDisallow: /tarieven\n"
        )]);

        $this->assertFalse(RobotsCheck::toegestaan('https://voorbeeld.nl/tarieven', self::UA));
    }

    public function test_allow_wint_bij_langere_match(): void
    {
        Http::fake(['*/robots.txt' => Http::response(
            "User-agent: *\nDisallow: /tarieven\nAllow: /tarieven/openbaar\n"
        )]);

        $this->assertTrue(RobotsCheck::toegestaan('https://voorbeeld.nl/tarieven/openbaar', self::UA));
        $this->assertFalse(RobotsCheck::toegestaan('https://voorbeeld.nl/tarieven/prive', self::UA));
    }

    public function test_onbereikbare_robots_txt_blokkeert_niet(): void
    {
        Http::fake(['*/robots.txt' => function (): void {
            throw new \Illuminate\Http\Client\ConnectionException('timeout');
        }]);

        $this->assertTrue(RobotsCheck::toegestaan('https://voorbeeld.nl/tarieven', self::UA));
    }
}
