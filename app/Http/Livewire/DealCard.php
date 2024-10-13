<?php

namespace App\Http\Livewire;

use Livewire\Component;

class DealCard extends Component
{
    public $deal;

    public function mount($deal)
    {
        $this->deal = $deal;
    }

    public function render()
    {
        return view('livewire.deal-card');
    }
}