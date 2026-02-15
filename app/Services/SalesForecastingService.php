<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\SalesForecast;
use App\Models\Pipeline;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesForecastingService
{
    /**
     * Generate forecast based on pipeline
     */
    public function generatePipelineForecast(Pipeline $pipeline, Carbon $startDate, Carbon $endDate): SalesForecast
    {
        $deals = Deal::where('pipeline_id', $pipeline->id)
            ->whereBetween('expected_close_date', [$startDate, $endDate])
            ->where('status', '!=', 'lost')
            ->get();

        $predictedRevenue = $deals->sum(function ($deal) {
            return $deal->value * ($deal->probability / 100);
        });

        return SalesForecast::create([
            'name' => "Pipeline Forecast: {$pipeline->name}",
            'period_start' => $startDate,
            'period_end' => $endDate,
            'forecast_type' => SalesForecast::TYPE_PIPELINE,
            'predicted_revenue' => $predictedRevenue,
            'confidence_level' => $this->calculateConfidence($deals),
            'deal_count' => $deals->count(),
            'pipeline_id' => $pipeline->id,
        ]);
    }

    /**
     * Generate forecast based on historical data
     */
    public function generateHistoricalForecast(Carbon $startDate, Carbon $endDate): SalesForecast
    {
        // Get historical data from same period last year
        $historicalStart = $startDate->copy()->subYear();
        $historicalEnd = $endDate->copy()->subYear();

        $historicalRevenue = Deal::where('status', 'won')
            ->whereBetween('closed_at', [$historicalStart, $historicalEnd])
            ->sum('value');

        // Apply growth factor based on recent trends
        $growthFactor = $this->calculateGrowthFactor();
        $predictedRevenue = $historicalRevenue * (1 + $growthFactor);

        return SalesForecast::create([
            'name' => 'Historical Forecast',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'forecast_type' => SalesForecast::TYPE_HISTORICAL,
            'predicted_revenue' => $predictedRevenue,
            'confidence_level' => 75,
            'metadata' => [
                'historical_revenue' => $historicalRevenue,
                'growth_factor' => $growthFactor,
            ],
        ]);
    }

    /**
     * Generate weighted forecast
     */
    public function generateWeightedForecast(Carbon $startDate, Carbon $endDate): SalesForecast
    {
        $deals = Deal::whereBetween('expected_close_date', [$startDate, $endDate])
            ->where('status', '!=', 'lost')
            ->with('stage')
            ->get();

        $weightedRevenue = $deals->sum(function ($deal) {
            $stageWeight = $this->getStageWeight($deal->stage);
            $probabilityWeight = $deal->probability / 100;
            
            return $deal->value * $stageWeight * $probabilityWeight;
        });

        return SalesForecast::create([
            'name' => 'Weighted Forecast',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'forecast_type' => SalesForecast::TYPE_WEIGHTED,
            'predicted_revenue' => $weightedRevenue,
            'confidence_level' => $this->calculateConfidence($deals),
            'deal_count' => $deals->count(),
        ]);
    }

    /**
     * Calculate confidence level based on deal data
     */
    protected function calculateConfidence($deals): float
    {
        if ($deals->isEmpty()) {
            return 0;
        }

        // Factors that increase confidence:
        // 1. More deals = more predictable
        // 2. Higher probability deals
        // 3. Deals further along in pipeline
        
        $dealCount = $deals->count();
        $avgProbability = $deals->avg('probability');
        
        $countScore = min(100, ($dealCount / 10) * 100); // Max at 10 deals
        $probabilityScore = $avgProbability;
        
        // Weighted average
        $confidence = ($countScore * 0.3) + ($probabilityScore * 0.7);
        
        return round($confidence, 2);
    }

    /**
     * Calculate growth factor from recent trends
     */
    protected function calculateGrowthFactor(): float
    {
        $currentQuarter = Deal::where('status', 'won')
            ->whereBetween('closed_at', [now()->startOfQuarter(), now()])
            ->sum('value');

        $previousQuarter = Deal::where('status', 'won')
            ->whereBetween('closed_at', [
                now()->subQuarter()->startOfQuarter(),
                now()->subQuarter()->endOfQuarter()
            ])
            ->sum('value');

        if ($previousQuarter == 0) {
            return 0;
        }

        return ($currentQuarter - $previousQuarter) / $previousQuarter;
    }

    /**
     * Get weight for a deal stage
     */
    protected function getStageWeight($stage): float
    {
        if (!$stage) {
            return 0.5;
        }

        // Earlier stages get lower weight
        $order = $stage->order ?? 0;
        $maxOrder = 5; // Typical number of stages
        
        return min(1, ($order / $maxOrder) + 0.2);
    }

    /**
     * Update forecast with actual results
     */
    public function updateForecastActuals(SalesForecast $forecast): void
    {
        $actualRevenue = Deal::where('status', 'won')
            ->whereBetween('closed_at', [$forecast->period_start, $forecast->period_end])
            ->sum('value');

        $forecast->update([
            'actual_revenue' => $actualRevenue,
        ]);
    }

    /**
     * Get forecast accuracy metrics
     */
    public function getForecastAccuracyMetrics(): array
    {
        $forecasts = SalesForecast::whereNotNull('actual_revenue')
            ->where('created_at', '>', now()->subYear())
            ->get();

        if ($forecasts->isEmpty()) {
            return [
                'average_accuracy' => 0,
                'best_forecast_type' => null,
                'total_forecasts' => 0,
            ];
        }

        $byType = $forecasts->groupBy('forecast_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'average_accuracy' => round($group->avg('accuracy'), 2),
            ];
        });

        return [
            'average_accuracy' => round($forecasts->avg('accuracy'), 2),
            'best_forecast_type' => $byType->sortByDesc('average_accuracy')->keys()->first(),
            'total_forecasts' => $forecasts->count(),
            'by_type' => $byType,
        ];
    }

    /**
     * Get revenue trend data
     */
    public function getRevenueTrend(int $months = 12): array
    {
        $data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startDate = $date->copy()->startOfMonth();
            $endDate = $date->copy()->endOfMonth();
            
            $revenue = Deal::where('status', 'won')
                ->whereBetween('closed_at', [$startDate, $endDate])
                ->sum('value');
            
            $data[] = [
                'month' => $date->format('M Y'),
                'revenue' => $revenue,
            ];
        }
        
        return $data;
    }
}
