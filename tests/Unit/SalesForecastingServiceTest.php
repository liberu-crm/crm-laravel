<?php

namespace Tests\Unit;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\SalesForecast;
use App\Models\Stage;
use App\Services\SalesForecastingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class SalesForecastingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SalesForecastingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SalesForecastingService;
    }

    /** Invoke a protected method for direct unit testing. */
    private function invokeProtected(string $method, array $args): mixed
    {
        $ref = new ReflectionMethod($this->service, $method);
        $ref->setAccessible(true);

        return $ref->invokeArgs($this->service, $args);
    }

    public function test_generate_weighted_forecast_sums_value_times_stage_and_probability_weight(): void
    {
        // getStageWeight = min(1, order/5 + 0.2)
        $stageOrder3 = Stage::factory()->create(['order' => 3]); // weight 0.8
        $stageOrder5 = Stage::factory()->create(['order' => 5]); // weight 1.0

        // In-range, not lost -> counted.
        Deal::factory()->create([
            'value' => 10000, 'probability' => 50, 'stage' => 'open',
            'stage_id' => $stageOrder3->id, 'close_date' => '2026-02-10',
        ]); // 10000 * 0.8 * 0.50 = 4000
        Deal::factory()->create([
            'value' => 20000, 'probability' => 80, 'stage' => 'open',
            'stage_id' => $stageOrder5->id, 'close_date' => '2026-02-20',
        ]); // 20000 * 1.0 * 0.80 = 16000

        // Excluded: stage is "lost".
        Deal::factory()->create([
            'value' => 99999, 'probability' => 90, 'stage' => 'lost',
            'stage_id' => $stageOrder5->id, 'close_date' => '2026-02-15',
        ]);
        // Excluded: close_date outside the window.
        Deal::factory()->create([
            'value' => 99999, 'probability' => 90, 'stage' => 'open',
            'stage_id' => $stageOrder3->id, 'close_date' => '2025-06-01',
        ]);

        $forecast = $this->service->generateWeightedForecast(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-03-31'),
        );

        $this->assertInstanceOf(SalesForecast::class, $forecast);
        $this->assertEqualsWithDelta(20000.0, (float) $forecast->predicted_revenue, 0.01);
        $this->assertSame(2, $forecast->deal_count);
        $this->assertDatabaseHas('sales_forecasts', [
            'id' => $forecast->id,
            'forecast_type' => SalesForecast::TYPE_WEIGHTED,
            'deal_count' => 2,
        ]);
    }

    public function test_get_stage_weight_for_known_orders_and_default(): void
    {
        // No stage -> default 0.5
        $this->assertSame(0.5, $this->invokeProtected('getStageWeight', [null]));

        // order/5 + 0.2, capped at 1.0
        $this->assertSame(0.2, $this->invokeProtected('getStageWeight', [new Stage(['order' => 0])]));
        $this->assertSame(0.8, $this->invokeProtected('getStageWeight', [new Stage(['order' => 3])]));
        $this->assertSame(1.0, $this->invokeProtected('getStageWeight', [new Stage(['order' => 5])]));
        // Cap kicks in for high orders.
        $this->assertSame(1.0, $this->invokeProtected('getStageWeight', [new Stage(['order' => 10])]));
    }

    public function test_calculate_confidence_returns_zero_for_empty_and_no_division_by_zero(): void
    {
        $this->assertEqualsWithDelta(0.0, (float) $this->invokeProtected('calculateConfidence', [collect([])]), 0.001);
    }

    public function test_calculate_confidence_weights_count_and_probability(): void
    {
        $deals = collect([
            Deal::factory()->make(['probability' => 50]),
            Deal::factory()->make(['probability' => 80]),
        ]);

        // countScore = min(100, 2/10*100) = 20; avgProbability = 65
        // confidence = 20*0.3 + 65*0.7 = 51.5
        $this->assertEqualsWithDelta(51.5, (float) $this->invokeProtected('calculateConfidence', [$deals]), 0.001);
    }

    public function test_generate_pipeline_forecast_is_scoped_to_the_pipeline(): void
    {
        $pipeline = Pipeline::factory()->create();
        $otherPipeline = Pipeline::factory()->create();

        // Counted: this pipeline, in range, not lost. value * probability/100.
        Deal::factory()->create([
            'pipeline_id' => $pipeline->id, 'value' => 10000, 'probability' => 50,
            'stage' => 'open', 'close_date' => '2026-02-10',
        ]); // 5000
        Deal::factory()->create([
            'pipeline_id' => $pipeline->id, 'value' => 40000, 'probability' => 25,
            'stage' => 'open', 'close_date' => '2026-02-20',
        ]); // 10000

        // Excluded: lost / other pipeline / out of range.
        Deal::factory()->create([
            'pipeline_id' => $pipeline->id, 'value' => 99999, 'probability' => 99,
            'stage' => 'lost', 'close_date' => '2026-02-15',
        ]);
        Deal::factory()->create([
            'pipeline_id' => $otherPipeline->id, 'value' => 99999, 'probability' => 99,
            'stage' => 'open', 'close_date' => '2026-02-15',
        ]);
        Deal::factory()->create([
            'pipeline_id' => $pipeline->id, 'value' => 99999, 'probability' => 99,
            'stage' => 'open', 'close_date' => '2025-01-01',
        ]);

        $forecast = $this->service->generatePipelineForecast(
            $pipeline,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-03-31'),
        );

        $this->assertInstanceOf(SalesForecast::class, $forecast);
        $this->assertSame($pipeline->id, $forecast->pipeline_id);
        $this->assertSame(SalesForecast::TYPE_PIPELINE, $forecast->forecast_type);
        $this->assertEqualsWithDelta(15000.0, (float) $forecast->predicted_revenue, 0.01);
        $this->assertSame(2, $forecast->deal_count);
        $this->assertDatabaseHas('sales_forecasts', [
            'id' => $forecast->id,
            'pipeline_id' => $pipeline->id,
            'forecast_type' => SalesForecast::TYPE_PIPELINE,
        ]);
    }
}
