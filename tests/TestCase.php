<?php

namespace Tests;

use App\Services\Mortgage\Constants;
use App\Services\Mortgage\Domain\RateRepository;
use App\Services\Mortgage\Domain\TaxRules;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // TaxRules, RateRepository en Constants cachen per PHP-proces; PHPUnit
        // draait alle tests in één proces, dus zonder reset lekt de
        // databasestate van het ene test-geval (en zijn transactie-rollback)
        // het volgende in.
        TaxRules::verversCache();
        RateRepository::verversCache();
        Constants::verversCache();
    }
}
