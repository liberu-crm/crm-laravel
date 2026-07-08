<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Filament\Portal\Resources\KnowledgeBaseArticleResource\Pages\ViewArticle;
use App\Models\KnowledgeBaseArticle;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PortalKbVoteTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private KnowledgeBaseArticle $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->team = Team::factory()->create();
        $this->article = KnowledgeBaseArticle::factory()->create([
            'team_id' => $this->team->id,
            'is_published' => true,
            'helpful_count' => 0,
        ]);
    }

    private function actAsCustomer(string $email = 'cust@example.com'): User
    {
        $user = User::factory()->create(['email' => $email, 'email_verified_at' => now()]);
        $user->forceFill(['current_team_id' => $this->team->id])->save();
        setPermissionsTeamId(null);
        $user->assignRole('customer');
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        return $user;
    }

    public function test_first_helpful_vote_counts(): void
    {
        $this->actAsCustomer();

        Livewire::test(ViewArticle::class, ['record' => $this->article->getKey()])
            ->callAction('helpful');

        $this->assertSame(1, (int) $this->article->fresh()->getAttribute('helpful_count'));
        $this->assertDatabaseHas('kb_article_votes', [
            'knowledge_base_article_id' => $this->article->id,
            'vote' => 'helpful',
        ]);
    }

    public function test_second_vote_by_same_customer_is_deduped(): void
    {
        $this->actAsCustomer();

        Livewire::test(ViewArticle::class, ['record' => $this->article->getKey()])
            ->callAction('helpful')
            ->callAction('not_helpful');

        $this->assertSame(1, (int) $this->article->fresh()->getAttribute('helpful_count'));
        $this->assertSame(0, (int) $this->article->fresh()->getAttribute('not_helpful_count'));
        // One vote row for this customer, despite two clicks.
        $this->assertSame(1, DB::table('kb_article_votes')->count());
    }

    public function test_different_customers_vote_independently(): void
    {
        $this->actAsCustomer('one@example.com');
        Livewire::test(ViewArticle::class, ['record' => $this->article->getKey()])->callAction('helpful');

        $this->actAsCustomer('two@example.com');
        Livewire::test(ViewArticle::class, ['record' => $this->article->getKey()])->callAction('helpful');

        $this->assertSame(2, (int) $this->article->fresh()->getAttribute('helpful_count'));
    }
}
