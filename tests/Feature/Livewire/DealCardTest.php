<?php

namespace Tests\Feature\Livewire;

use App\Livewire\DealCard;
use App\Models\Deal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_mounts_with_a_deal_and_renders_its_details(): void
    {
        $deal = Deal::factory()->create(['name' => 'Acme expansion', 'stage' => 'proposal']);

        Livewire::test(DealCard::class, ['deal' => $deal])
            ->assertStatus(200)
            ->assertSet('deal.id', $deal->id)
            ->assertSee('Acme expansion')
            ->assertSee('proposal');
    }

    public function test_it_renders_without_a_deal(): void
    {
        // mount() accepts mixed; the view null-guard must not fatal when unset.
        Livewire::test(DealCard::class, ['deal' => null])
            ->assertStatus(200);
    }
}
