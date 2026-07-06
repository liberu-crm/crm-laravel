<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Services\DataEnrichmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DataEnrichmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private DataEnrichmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.data_enrichment.api_key' => 'test-key']);
        $this->service = new DataEnrichmentService;
    }

    public function test_enrich_updates_fillable_fields_and_returns_true(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response([
                'industry' => 'Software',
                'size' => '500-1000',
                'location' => 'Berlin, DE',
                'annual_revenue' => '9000000',
            ], 200),
        ]);

        $company = Company::factory()->create([
            'domain' => 'acme.test',
            'industry' => 'Old',
            'size' => 'tiny',
            'location' => 'nowhere',
        ]);

        $this->assertTrue($this->service->enrichCompanyData($company));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'industry' => 'Software',
            'size' => '500-1000',
            'location' => 'Berlin, DE',
        ]);

        $company->refresh();
        $this->assertEquals(9000000, $company->annual_revenue);
    }

    public function test_enrich_returns_false_and_leaves_company_untouched_on_failure(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response(['error' => 'boom'], 500),
        ]);

        $company = Company::factory()->create([
            'domain' => 'acme.test',
            'industry' => 'Old',
            'size' => 'tiny',
            'location' => 'nowhere',
        ]);

        $this->assertFalse($this->service->enrichCompanyData($company));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'industry' => 'Old',
            'size' => 'tiny',
            'location' => 'nowhere',
        ]);
    }

    public function test_enrich_keeps_existing_values_for_fields_the_api_omits(): void
    {
        Http::fake([
            // Response only carries `industry`; size/location are absent.
            'api.example.com/*' => Http::response(['industry' => 'Fintech'], 200),
        ]);

        $company = Company::factory()->create([
            'domain' => 'acme.test',
            'industry' => 'Old',
            'size' => 'medium',
            'location' => 'Paris, FR',
        ]);

        $this->assertTrue($this->service->enrichCompanyData($company));

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'industry' => 'Fintech',
            'size' => 'medium',      // preserved via null-coalescing
            'location' => 'Paris, FR', // preserved via null-coalescing
        ]);
    }

    public function test_enrich_sends_company_domain_with_bearer_token(): void
    {
        Http::fake([
            'api.example.com/*' => Http::response([], 200),
        ]);

        $company = Company::factory()->create(['domain' => 'lookup-me.test']);

        $this->service->enrichCompanyData($company);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'api.example.com/v1/companies')
                && $request['domain'] === 'lookup-me.test'
                && $request->hasHeader('Authorization', 'Bearer test-key');
        });
    }
}
