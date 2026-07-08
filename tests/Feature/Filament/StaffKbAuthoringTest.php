<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\KnowledgeBaseArticleResource as StaffResource;
use App\Filament\App\Resources\KnowledgeBaseArticleResource\Pages\CreateKnowledgeBaseArticle;
use App\Filament\App\Resources\KnowledgeBaseArticleResource\Pages\EditKnowledgeBaseArticle;
use App\Filament\App\Resources\KnowledgeBaseArticleResource\Pages\ListKnowledgeBaseArticles;
use App\Filament\Portal\Resources\KnowledgeBaseArticleResource as PortalResource;
use App\Models\KnowledgeBaseArticle;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StaffKbAuthoringTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private $team;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->manager = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $this->team = $this->manager->currentTeam;
        setPermissionsTeamId($this->team->id);
        $this->manager->assignRole('manager');
        $this->actingAs($this->manager);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($this->team);
    }

    public function test_manager_creates_article_scoped_to_team_and_published_by_default(): void
    {
        Livewire::test(CreateKnowledgeBaseArticle::class)
            ->fillForm([
                'title' => 'How to reset your password',
                'category' => 'Account',
                'content' => 'Click forgot password and follow the emailed link.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('knowledge_base_articles', [
            'title' => 'How to reset your password',
            'team_id' => $this->team->id,
            'is_published' => 1,
        ]);
    }

    public function test_list_is_team_scoped(): void
    {
        $mine = KnowledgeBaseArticle::factory()->create(['team_id' => $this->team->id]);
        $other = KnowledgeBaseArticle::factory()->create(['team_id' => Team::factory()->create()->id]);

        Livewire::test(ListKnowledgeBaseArticles::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_manager_can_access_sales_rep_cannot(): void
    {
        $this->assertTrue(StaffResource::canAccess());

        $rep = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        setPermissionsTeamId($rep->currentTeam->id);
        $rep->assignRole('sales_rep');
        $this->actingAs($rep);

        $this->assertFalse(StaffResource::canAccess());
    }

    public function test_draft_is_hidden_from_the_portal_browse(): void
    {
        $draft = KnowledgeBaseArticle::factory()->create(['team_id' => $this->team->id, 'is_published' => false]);
        $published = KnowledgeBaseArticle::factory()->create(['team_id' => $this->team->id, 'is_published' => true]);

        // A customer of the same team, on the (non-tenant) portal panel.
        $customer = User::factory()->create(['email_verified_at' => now()]);
        $customer->forceFill(['current_team_id' => $this->team->id])->save();
        setPermissionsTeamId(null);
        $customer->assignRole('customer');
        $this->actingAs($customer);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        $visible = PortalResource::getEloquentQuery()->pluck('id');

        $this->assertFalse($visible->contains($draft->id));
        $this->assertTrue($visible->contains($published->id));
    }

    public function test_edit_publishes_a_draft(): void
    {
        $draft = KnowledgeBaseArticle::factory()->create(['team_id' => $this->team->id, 'is_published' => false]);

        Livewire::test(EditKnowledgeBaseArticle::class, ['record' => $draft->getKey()])
            ->fillForm(['is_published' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue((bool) $draft->fresh()->getAttribute('is_published'));
    }
}
