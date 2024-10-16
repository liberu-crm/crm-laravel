<?php

namespace Tests\Feature\Filament\Pages;

use App\Filament\Pages\ReportCustomizer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportCustomizerTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_view_report_customizer_page()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(ReportCustomizer::getUrl())
            ->assertSuccessful();
    }

    public function test_can_generate_contact_interactions_report()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportCustomizer::class)
            ->set('reportType', 'contact-interactions')
            ->set('startDate', now()->subDays(30)->toDateString())
            ->set('endDate', now()->toDateString())
            ->call('generateReport')
            ->assertSet('data.type', 'pie')
            ->assertSet('data.data.datasets.0.label', 'Activities count');
    }

    public function test_can_generate_sales_pipeline_report()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportCustomizer::class)
            ->set('reportType', 'sales-pipeline')
            ->set('startDate', now()->subDays(30)->toDateString())
            ->set('endDate', now()->toDateString())
            ->call('generateReport')
            ->assertSet('data.type', 'bar')
            ->assertSet('data.data.datasets.0.label', 'Total value');
    }

    public function test_can_generate_customer_engagement_report()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportCustomizer::class)
            ->set('reportType', 'customer-engagement')
            ->set('startDate', now()->subDays(30)->toDateString())
            ->set('endDate', now()->toDateString())
            ->call('generateReport')
            ->assertSet('data.type', 'line')
            ->assertSet('data.data.datasets.0.label', 'Count');
    }

    public function test_can_export_pdf_report()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportCustomizer::class)
            ->set('reportType', 'contact-interactions')
            ->set('startDate', now()->subDays(30)->toDateString())
            ->set('endDate', now()->toDateString())
            ->call('exportPdf')
            ->assertDispatched('report-exported');
    }

    public function test_can_export_csv_report()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ReportCustomizer::class)
            ->set('reportType', 'contact-interactions')
            ->set('startDate', now()->subDays(30)->toDateString())
            ->set('endDate', now()->toDateString())
            ->call('exportCsv')
            ->assertDispatched('report-exported');
    }
}