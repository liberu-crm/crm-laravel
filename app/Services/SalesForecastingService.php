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
     * Generate an AI-powered forecast using linear regression on historical monthly revenue.
     *
     * The method fits a simple ordinary-least-squares regression line to the last
     * $trainingMonths months of won-deal revenue and extrapolates it to the requested
     * period.  The confidence level is derived from the coefficient of determination
     * (R²) of the regression so that periods with highly variable history get a lower
     * confidence score.
     */
    public function generateAiForecast(Carbon $startDate, Carbon $endDate, int $trainingMonths = 12): SalesForecast
    {
        $trend = $this->getRevenueTrend($trainingMonths);

        [$slope, $intercept] = $this->linearRegression(
            array_column($trend, 'revenue')
        );

        $seasonalFactors = $this->calculateSeasonalFactors($trend);

        // Predict revenue for each month in the requested window
        $predictedRevenue = 0.0;
        $forecastMonths   = 0;
        $current = $startDate->copy()->startOfMonth();

        while ($current->lte($endDate)) {
            $monthIndex   = $trainingMonths + $forecastMonths;
            $base         = $intercept + $slope * $monthIndex;
            $seasonalKey  = $current->month;
            $seasonal     = $seasonalFactors[$seasonalKey] ?? 1.0;
            $predictedRevenue += max(0, $base * $seasonal);
            $forecastMonths++;
            $current->addMonth();
        }

        $rSquared   = $this->calculateRSquared(array_column($trend, 'revenue'), $slope, $intercept);
        $confidence = (int) round(max(0, min(100, $rSquared * 100)));

        return SalesForecast::create([
            'name'              => 'AI Linear-Regression Forecast',
            'period_start'      => $startDate,
            'period_end'        => $endDate,
            'forecast_type'     => SalesForecast::TYPE_AI_PREDICTED,
            'predicted_revenue' => round($predictedRevenue, 2),
            'confidence_level'  => $confidence,
            'metadata'          => [
                'slope'            => round($slope, 4),
                'intercept'        => round($intercept, 4),
                'r_squared'        => round($rSquared, 4),
                'training_months'  => $trainingMonths,
                'seasonal_factors' => $seasonalFactors,
            ],
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
                'month'   => $date->format('M Y'),
                'revenue' => (float) $revenue,
                'month_number' => (int) $date->month,
            ];
        }
        
        return $data;
    }

    // -------------------------------------------------------------------------
    // Private helpers for the AI forecasting
    // -------------------------------------------------------------------------

    /**
     * Ordinary-least-squares linear regression on an array of y values
     * (x = 0, 1, 2, ...).
     *
     * @param  float[] $values
     * @return array{float, float}  [slope, intercept]
     */
    private function linearRegression(array $values): array
    {
        $n = count($values);

        if ($n < 2) {
            return [0.0, (float) ($values[0] ?? 0)];
        }

        $sumX  = 0.0;
        $sumY  = 0.0;
        $sumXY = 0.0;
        $sumX2 = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $sumX  += $i;
            $sumY  += $values[$i];
            $sumXY += $i * $values[$i];
            $sumX2 += $i * $i;
        }

        $denominator = ($n * $sumX2 - $sumX * $sumX);

        if ($denominator === 0.0) {
            return [0.0, $sumY / $n];
        }

        $slope     = ($n * $sumXY - $sumX * $sumY) / $denominator;
        $intercept = ($sumY - $slope * $sumX) / $n;

        return [$slope, $intercept];
    }

    /**
     * Calculate the coefficient of determination (R²) for a linear fit.
     *
     * @param  float[] $values
     */
    private function calculateRSquared(array $values, float $slope, float $intercept): float
    {
        $n    = count($values);
        $mean = array_sum($values) / max(1, $n);
        $ssTot = 0.0;
        $ssRes = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $predicted = $intercept + $slope * $i;
            $ssTot    += ($values[$i] - $mean) ** 2;
            $ssRes    += ($values[$i] - $predicted) ** 2;
        }

        if ($ssTot === 0.0) {
            return 1.0;
        }

        return max(0.0, 1.0 - ($ssRes / $ssTot));
    }

    /**
     * Calculate monthly seasonal factors from historical data.
     * Each factor is the ratio of that month's average to the overall monthly average.
     *
     * @param  array<array{revenue: float, month_number: int}> $trend
     * @return array<int, float>  Keys are month numbers (1–12)
     */
    private function calculateSeasonalFactors(array $trend): array
    {
        $byMonth = [];

        foreach ($trend as $point) {
            $month = $point['month_number'];
            $byMonth[$month][] = $point['revenue'];
        }

        if (empty($byMonth)) {
            return array_fill_keys(range(1, 12), 1.0);
        }

        $monthlyAverages = [];
        foreach ($byMonth as $month => $revenues) {
            $monthlyAverages[$month] = array_sum($revenues) / count($revenues);
        }

        $overallAverage = array_sum($monthlyAverages) / count($monthlyAverages);

        if ($overallAverage === 0.0) {
            return array_fill_keys(array_keys($monthlyAverages), 1.0);
        }

        $factors = [];
        foreach ($monthlyAverages as $month => $avg) {
            $factors[$month] = $avg / $overallAverage;
        }

        return $factors;
    }
}
