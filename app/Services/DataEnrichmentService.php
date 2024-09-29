<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Http;

class DataEnrichmentService
{
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.data_enrichment.api_key');
    }

    public function enrichCompanyData(Company $company)
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
        ])->get("https://api.example.com/v1/companies", [
            'domain' => $company->domain,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            
            $company->update([
                'industry' => $data['industry'] ?? $company->industry,
                'size' => $data['size'] ?? $company->size,
                'location' => $data['location'] ?? $company->location,
                'annual_revenue' => $data['annual_revenue'] ?? $company->annual_revenue,
            ]);

            return true;
        }

        return false;
    }
}