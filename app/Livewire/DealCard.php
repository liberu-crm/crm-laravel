<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Component;

class DealCard extends Component
{
    public $deal;

    public function mount(mixed $deal): void
    {
        $this->deal = $deal;
    }

    // ponytail: inline Blade — the `livewire.deal-card` file view never existed,
    // so `view('livewire.deal-card')` fataled on every render. Livewire renders a
    // string return as a Blade template. Move to a file view when the markup grows.
    public function render(): string
    {
        return <<<'HTML'
            <div class="deal-card">
                @if ($deal)
                    <h3 class="deal-name">{{ $deal->name }}</h3>
                    <span class="deal-stage">{{ $deal->stage }}</span>
                    <span class="deal-value">{{ $deal->value }}</span>
                @endif
            </div>
        HTML;
    }
}
