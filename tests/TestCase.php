<?php

declare(strict_types=1);

namespace Tests;

use App\Support\AccessContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function tearDown(): void
    {
        // Process-level statics survive PHPUnit's per-test app reset, so a test
        // that leaves a tenant/owner scope or a frozen clock set would silently
        // poison later tests. Reset them for every test.
        TenantContext::clear();
        AccessContext::clear();
        Carbon::setTestNow();

        parent::tearDown();
    }
}
