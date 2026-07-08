<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\DealResource\Pages\EditDeal;
use App\Filament\App\Resources\DealResource\Pages\ListDeals;
use App\Models\Deal;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DealValueMaskingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Deal}
     */
    private function setUpViewer(string $role): array
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);

        // Owner-scoped: the deal must belong to the viewer to be visible to a rep/free.
        $deal = Deal::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'value' => 50000,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $deal];
    }

    public function test_free_user_serialization_masks_value_without_mutation(): void
    {
        [, $deal] = $this->setUpViewer('free');
        $fresh = $deal->fresh();

        $this->assertSame('[hidden]', $fresh->toArray()['value']);
        // Direct access is unmasked (no corruption).
        $this->assertSame('50000.00', (string) $fresh->value);
    }

    public function test_manager_serialization_shows_real_value(): void
    {
        [, $deal] = $this->setUpViewer('manager');

        $this->assertSame('50000.00', (string) $deal->fresh()->toArray()['value']);
    }

    public function test_free_user_sees_masked_value_in_the_table(): void
    {
        $this->setUpViewer('free');

        Livewire::test(ListDeals::class)
            ->assertSee('[hidden]')
            ->assertDontSee('50,000');
    }

    public function test_free_user_value_field_is_hidden_on_edit(): void
    {
        [, $deal] = $this->setUpViewer('free');

        Livewire::test(EditDeal::class, ['record' => $deal->getKey()])
            ->assertFormFieldIsHidden('value')
            ->assertSee('[hidden]');
    }

    public function test_manager_sees_the_real_value_on_edit(): void
    {
        [, $deal] = $this->setUpViewer('manager');

        Livewire::test(EditDeal::class, ['record' => $deal->getKey()])
            ->assertFormSet(['value' => '50000.00']);
    }
}
