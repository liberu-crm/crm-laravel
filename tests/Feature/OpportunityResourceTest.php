<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\App\Resources\OpportunityResource;
use Filament\Tables\Table;

class OpportunityResourceTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_get_pipeline_table_returns_table_instance()
    {
        $table = OpportunityResource::getPipelineTable(new Table);
        $this->assertInstanceOf(Table::class, $table);
    }

    public function test_pipeline_table_has_expected_columns()
    {
        $table = OpportunityResource::getPipelineTable(new Table);
        $columns = $table->getColumns();

        $this->assertCount(3, $columns);
        $this->assertEquals('stage', $columns[0]->getName());
        $this->assertEquals('deal_size', $columns[1]->getName());
        $this->assertEquals('closing_date', $columns[2]->getName());
    }

    public function test_pipeline_view_renders_correctly()
    {
        Opportunity::factory()->count(5)->create();

        $response = $this->get(OpportunityResource::getUrl('index'));

        $response->assertStatus(200);
        $response->assertViewIs('filament.app.resources.opportunity-resource.pages.list-opportunities');
        $response->assertSee('opportunity-pipeline-wrapper');
    }
}