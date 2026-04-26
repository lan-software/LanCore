<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Force deterministic filesystem disk roles for tests, regardless
        // of what the developer's local .env points at. Tests can override
        // this individually by calling config()->set() in beforeEach.
        config()->set('filesystems.default', 'local');
        config()->set('filesystems.public_disk', 'public');
        config()->set('filesystems.private_disk', 'local');
    }

    protected function skipUnlessFortifyFeature(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
