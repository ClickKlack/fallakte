<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Tests dürfen nie echte HTTP-Anfragen stellen (z. B. an Wikipedia).
        Http::preventStrayRequests();
    }
}
