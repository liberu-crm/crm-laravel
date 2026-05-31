<?php

declare(strict_types=1);

namespace App\Http\Livewire\Widgets;

use App\Models\DashboardWidget;
use Livewire\Component;

class Tasks extends Component
{
    public DashboardWidget $widget;

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.widgets.tasks');
    }
}
