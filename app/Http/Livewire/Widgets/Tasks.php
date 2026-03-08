<?php

namespace App\Http\Livewire\Widgets;

use App\Models\DashboardWidget;
use Livewire\Component;

class Tasks extends Component
{
    public DashboardWidget $widget;

    public function render()
    {
        return view('livewire.widgets.tasks');
    }
}
