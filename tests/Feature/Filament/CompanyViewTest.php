<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\CompanyResource\Pages\ViewCompany;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyViewTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Company}
     */
    private function setUpViewer(string $role): array
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $user->currentTeam;
        setPermissionsTeamId($team->id);
        $user->assignRole($role);

        $company = Company::factory()->create([
            'team_id' => $team->id,
            'phone_number' => '+15551234567',
            'annual_revenue' => 5000000,
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $company];
    }

    public function test_admin_can_mount_the_view_page(): void
    {
        [, $company] = $this->setUpViewer('admin');

        Livewire::test(ViewCompany::class, ['record' => $company->getKey()])
            ->assertOk();
    }

    public function test_free_user_sees_masked_sensitive_fields(): void
    {
        [, $company] = $this->setUpViewer('free');

        Livewire::test(ViewCompany::class, ['record' => $company->getKey()])
            ->assertSee('[hidden]')
            ->assertDontSee('+15551234567')
            ->assertDontSee('5000000');
    }

    public function test_manager_sees_the_real_phone(): void
    {
        [, $company] = $this->setUpViewer('manager');

        Livewire::test(ViewCompany::class, ['record' => $company->getKey()])
            ->assertSee('+15551234567')
            ->assertDontSee('[hidden]');
    }
}
