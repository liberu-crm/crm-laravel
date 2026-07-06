<?php

namespace Tests\Feature\Queue;

use App\Jobs\Concerns\TenantAware;
use App\Support\TenantContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Tests\TestCase;

/**
 * Test job: opts into TenantAware and captures the tenant in its constructor.
 * handle() records whatever tenant the worker saw at run time.
 */
class RecordingTenantJob implements ShouldQueue
{
    use Dispatchable, SerializesModels, TenantAware;

    public static ?int $seenTenantId = null;

    public function __construct()
    {
        $this->captureTenant();
    }

    public function handle(): void
    {
        self::$seenTenantId = TenantContext::currentId();
    }
}

class TenantAwareJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RecordingTenantJob::$seenTenantId = null;
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_middleware_restores_the_captured_tenant_inside_the_worker(): void
    {
        // Dispatch happens inside a tenant context...
        TenantContext::set(42);
        $job = new RecordingTenantJob;

        // ...but the worker runs in a fresh, un-scoped process.
        TenantContext::clear();
        $this->assertNull(TenantContext::currentId());

        $this->runThroughMiddleware($job);

        $this->assertSame(42, RecordingTenantJob::$seenTenantId);
        // Context is cleared again so the worker can't leak it to the next job.
        $this->assertNull(TenantContext::currentId());
    }

    public function test_job_dispatched_with_no_tenant_captures_and_restores_null(): void
    {
        $job = new RecordingTenantJob; // no context set
        $this->assertNull($job->tenantId);

        $this->runThroughMiddleware($job);

        $this->assertNull(RecordingTenantJob::$seenTenantId);
    }

    private function runThroughMiddleware(RecordingTenantJob $job): void
    {
        // Single middleware — invoke it directly around handle().
        $job->middleware()[0]->handle($job, fn ($job) => $job->handle());
    }
}
