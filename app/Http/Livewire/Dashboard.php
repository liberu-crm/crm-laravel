<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\DashboardWidget;

class Dashboard extends Component
{
    public $widgets;

    public function mount()
    {
        $this->widgets = auth()->user()->dashboardWidgets()->orderBy('position')->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }

    public function updateWidgetOrder($orderedIds)
    {
        foreach ($orderedIds as $position => $id) {
            DashboardWidget::where('id', $id)->update(['position' => $position + 1]);
        }
    }

    public function addWidget($type)
    {
        $position = $this->widgets->count() + 1;
        $widget = auth()->user()->dashboardWidgets()->create([
            'widget_type' => $type,
            'position' => $position,
        ]);
        $this->widgets->push($widget);
    }

    public function removeWidget($widgetId)
    {
        DashboardWidget::destroy($widgetId);
        $this->widgets = $this->widgets->reject(function ($widget) use ($widgetId) {
            return $widget->id == $widgetId;
        })->values();
    }
}