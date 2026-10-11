<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Senin pukul 10.00 — hari operasional, sebelum jam buka studio. */
    protected function seninPagi(): Carbon
    {
        $waktu = Carbon::parse('2030-01-07 10:00:00');
        Carbon::setTestNow($waktu);

        return $waktu;
    }
}
