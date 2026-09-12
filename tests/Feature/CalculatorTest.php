<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class CalculatorTest extends TestCase
{
    public function test_homepage_toont_wizardstap_1(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Accurity', false);
        $response->assertSee('design-data', false);
    }

    public function test_skip_wizard_toont_geavanceerd_resultaat(): void
    {
        $response = $this->post('/', ['do' => 'skipWizard']);

        $response->assertOk();
        $response->assertSee('data-theme="light"', false);
    }
}
