<?php

namespace App\Livewire;

use App\Models\DashboardWidget;
use Livewire\Component;

class Dashboard extends Component
{
    public $widgets;

    public function mount(): void
    {
        $this->widgets = auth()->user()->dashboardWidgets()->orderBy('position')->get();
    }

    public function updateWidgetOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            DashboardWidget::where('id', $id)->update(['position' => $position + 1]);
        }
    }

    public function addWidget(string $type): void
    {
        $position = $this->widgets->count() + 1;
        $widget = auth()->user()->dashboardWidgets()->create([
            'widget_type' => $type,
            'position' => $position,
        ]);
        $this->widgets->push($widget);
    }

    public function removeWidget(int $widgetId): void
    {
        DashboardWidget::destroy($widgetId);
        $this->widgets = $this->widgets->reject(fn ($widget): bool => $widget->id == $widgetId)->values();
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.dashboard');
    }
}
