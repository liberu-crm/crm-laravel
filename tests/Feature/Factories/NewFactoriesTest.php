<?php

declare(strict_types=1);

namespace Tests\Feature\Factories;

use App\Models\Ad;
use App\Models\AdSet;
use App\Models\Campaign;
use Database\Factories\ActivationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_factory_persists_a_row(): void
    {
        $this->assertModelExists(Campaign::factory()->create());
    }

    public function test_ad_set_factory_persists_a_row(): void
    {
        $this->assertModelExists(AdSet::factory()->create());
    }

    public function test_ad_factory_persists_a_row(): void
    {
        $this->assertModelExists(Ad::factory()->create());
    }

    /**
     * App\Models\Activation lacks the HasFactory trait (its three siblings have
     * it), so Activation::factory() is undefined. Drive the factory class
     * directly — this still asserts a genuine persisted Activation row. Adding
     * `use HasFactory;` to the model would enable Activation::factory() and let
     * ResourceEditPageMountTest cover ActivationResource.
     */
    public function test_activation_factory_persists_a_row(): void
    {
        $this->assertModelExists(ActivationFactory::new()->create());
    }
}
