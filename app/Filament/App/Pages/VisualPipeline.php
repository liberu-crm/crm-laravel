<?php

namespace App\Filament\App\Pages;

use App\Models\Deal;
use App\Models\Pipeline;
use App\Models\Stage;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class VisualPipeline extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected string $view = 'filament.app.pages.visual-pipeline';

    public ?Pipeline $pipeline = null;

    /** @var Collection<int, Stage> */
    public Collection $stages;

    /** @var Collection<int|string, Collection<int, Deal>> */
    public Collection $deals;

    public function mount(): void
    {
        $this->pipeline = Pipeline::query()
            ->where('is_active', true)
            ->with('stages')
            ->first()
            ?? Pipeline::query()->with('stages')->first();

        if ($this->pipeline === null) {
            $this->stages = collect();
            $this->deals = collect();

            return;
        }

        $this->loadPipelineData();
    }

    public function updateDealStage(int $dealId, int $newStageId): void
    {
        if ($this->pipeline === null) {
            return;
        }

        $deal = Deal::findOrFail($dealId);
        $deal->update(['stage_id' => $newStageId]);
        $this->loadPipelineData();
    }

    private function loadPipelineData(): void
    {
        $this->stages = $this->pipeline->stages;
        $this->deals = Deal::query()
            ->where('pipeline_id', $this->pipeline->id)
            ->get()
            ->groupBy('stage_id');
    }
}
