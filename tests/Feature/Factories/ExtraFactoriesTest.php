<?php

declare(strict_types=1);

namespace Tests\Feature\Factories;

use App\Models\Note;
use App\Models\OAuthConfiguration;
use App\Models\SocialMediaPost;
use App\Models\WhatsAppNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * These four models previously had no factory, so their Filament edit pages
 * could not be smoke-tested. Each factory must seed a persistable row.
 * Runs un-scoped (no tenant context), so IsTenantModel stamps nothing.
 */
class ExtraFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_note_factory_persists_a_row(): void
    {
        Note::factory()->create();
        $this->assertDatabaseCount('notes', 1);
    }

    public function test_oauth_configuration_factory_persists_a_row(): void
    {
        OAuthConfiguration::factory()->create();
        $this->assertDatabaseCount('oauth_configurations', 1);
    }

    public function test_social_media_post_factory_persists_a_row(): void
    {
        SocialMediaPost::factory()->create();
        $this->assertDatabaseCount('social_media_posts', 1);
    }

    public function test_whatsapp_number_factory_persists_a_row(): void
    {
        WhatsAppNumber::factory()->create();
        $this->assertDatabaseCount('whats_app_numbers', 1);
    }
}
