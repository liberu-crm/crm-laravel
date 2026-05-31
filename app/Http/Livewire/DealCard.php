<?php

declare(strict_types=1);

namespace App\Http\Livewire;

use Livewire\Component;

class DealCard extends Component
{
    public $deal;

    public function mount($deal): void
    {
        $this->deal = $deal;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
    {
        return view('livewire.deal-card');
    }
}
