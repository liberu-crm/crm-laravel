<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\CompanyResource\Pages\EditCompany;
use App\Filament\App\Resources\CompanyResource\Pages\ListCompanies;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CompanyMaskingUiTest extends TestCase
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

        // Company is tenant-scoped only (no territory/owner restriction), so a
        // team member sees every company in the team.
        $company = Company::factory()->create([
            'team_id' => $team->id,
            'name' => 'Acme',
            'phone_number' => '+15551234567',
        ]);

        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        return [$user, $company];
    }

    public function test_free_user_sees_masked_phone_in_the_table(): void
    {
        $this->setUpViewer('free');

        Livewire::test(ListCompanies::class)
            ->assertSee('[hidden]')
            ->assertDontSee('+15551234567');
    }

    public function test_manager_sees_the_real_phone_in_the_table(): void
    {
        $this->setUpViewer('manager');

        Livewire::test(ListCompanies::class)
            ->assertSee('+15551234567');
    }

    public function test_free_user_cannot_find_a_company_by_searching_its_masked_phone(): void
    {
        [, $company] = $this->setUpViewer('free');

        Livewire::test(ListCompanies::class)
            ->assertCanSeeTableRecords([$company])
            ->searchTable('+15551234567')
            ->assertCanNotSeeTableRecords([$company]);
    }

    public function test_phone_is_not_searchable_now_that_it_is_encrypted(): void
    {
        // phone_number is encrypted at rest with no blind index, so it is no
        // longer searchable for anyone (not just masked roles).
        [, $company] = $this->setUpViewer('manager');

        Livewire::test(ListCompanies::class)
            ->searchTable('+15551234567')
            ->assertCanNotSeeTableRecords([$company]);
    }

    public function test_free_user_sees_masked_phone_in_the_edit_form(): void
    {
        [, $company] = $this->setUpViewer('free');

        Livewire::test(EditCompany::class, ['record' => $company->getKey()])
            ->assertSee('[hidden]')
            ->assertDontSee('+15551234567');
    }

    public function test_real_phone_field_is_hidden_from_a_free_user_on_edit(): void
    {
        // A hidden Filament field is neither validated nor dehydrated, so a save
        // cannot overwrite the stored phone with the mask — no corruption.
        [, $company] = $this->setUpViewer('free');

        Livewire::test(EditCompany::class, ['record' => $company->getKey()])
            ->assertFormFieldIsHidden('phone_number');
    }

    public function test_manager_sees_the_real_phone_in_the_edit_form(): void
    {
        [, $company] = $this->setUpViewer('manager');

        Livewire::test(EditCompany::class, ['record' => $company->getKey()])
            ->assertFormSet(['phone_number' => '+15551234567']);
    }
}
