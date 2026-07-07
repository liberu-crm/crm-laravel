<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Filament\Portal\Resources\KnowledgeBaseArticleResource\Pages\ListArticles;
use App\Filament\Portal\Resources\KnowledgeBaseArticleResource\Pages\ViewArticle;
use App\Models\KnowledgeBaseArticle;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PortalKnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private function actingCustomer(): User
    {
        $this->seed(RolesSeeder::class);
        $this->team = Team::factory()->create();
        $customer = User::factory()->create(['email_verified_at' => now()]);
        $customer->forceFill(['current_team_id' => $this->team->id])->save();
        setPermissionsTeamId(null);
        $customer->assignRole('customer');

        $this->actingAs($customer);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        return $customer;
    }

    public function test_lists_only_own_team_published_articles(): void
    {
        $this->actingCustomer();
        $mine = KnowledgeBaseArticle::factory()->create(['team_id' => $this->team->id, 'is_published' => true]);
        $draft = KnowledgeBaseArticle::factory()->create(['team_id' => $this->team->id, 'is_published' => false]);
        $otherTeam = KnowledgeBaseArticle::factory()->create(['is_published' => true]);

        Livewire::test(ListArticles::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$draft, $otherTeam]);
    }

    public function test_unpublished_or_foreign_article_is_not_found(): void
    {
        $this->actingCustomer();
        $published = KnowledgeBaseArticle::factory()->create(['team_id' => $this->team->id, 'is_published' => true]);
        $draft = KnowledgeBaseArticle::factory()->create(['team_id' => $this->team->id, 'is_published' => false]);
        $foreign = KnowledgeBaseArticle::factory()->create(['is_published' => true]);

        $this->get('/portal/articles/'.$published->id)->assertOk();
        $this->get('/portal/articles/'.$draft->id)->assertNotFound();
        $this->get('/portal/articles/'.$foreign->id)->assertNotFound();
    }

    public function test_helpful_action_increments_helpful_count(): void
    {
        $this->actingCustomer();
        $article = KnowledgeBaseArticle::factory()->create([
            'team_id' => $this->team->id,
            'is_published' => true,
            'helpful_count' => 4,
        ]);

        Livewire::test(ViewArticle::class, ['record' => $article->getKey()])
            ->callAction('helpful');

        $this->assertSame(5, (int) $article->fresh()->helpful_count);
    }

    public function test_not_helpful_action_increments_not_helpful_count(): void
    {
        $this->actingCustomer();
        $article = KnowledgeBaseArticle::factory()->create([
            'team_id' => $this->team->id,
            'is_published' => true,
            'not_helpful_count' => 1,
        ]);

        Livewire::test(ViewArticle::class, ['record' => $article->getKey()])
            ->callAction('not_helpful');

        $this->assertSame(2, (int) $article->fresh()->not_helpful_count);
    }
}
