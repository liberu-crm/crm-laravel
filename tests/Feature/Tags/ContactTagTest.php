<?php

namespace Tests\Feature\Tags;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_tags_can_be_attached_to_a_contact_and_read_back(): void
    {
        $contact = Contact::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $contact->tags()->attach($tags);

        $this->assertCount(2, $contact->refresh()->tags);
        $this->assertEqualsCanonicalizing(
            $tags->pluck('name')->all(),
            $contact->tags->pluck('name')->all()
        );
    }

    public function test_a_tag_can_be_attached_to_multiple_contacts(): void
    {
        $tag = Tag::factory()->create();
        $contacts = Contact::factory()->count(2)->create();

        $tag->contacts()->attach($contacts);

        $this->assertCount(2, $tag->refresh()->contacts);
    }

    public function test_sync_replaces_tags(): void
    {
        $contact = Contact::factory()->create();
        [$a, $b, $c] = Tag::factory()->count(3)->create()->all();

        $contact->tags()->attach([$a->id, $b->id]);
        $contact->tags()->sync([$c->id]);

        $this->assertEquals([$c->id], $contact->refresh()->tags->pluck('id')->all());
    }

    public function test_detach_removes_one_tag(): void
    {
        $contact = Contact::factory()->create();
        [$a, $b] = Tag::factory()->count(2)->create()->all();

        $contact->tags()->attach([$a->id, $b->id]);
        $contact->tags()->detach($a->id);

        $this->assertEquals([$b->id], $contact->refresh()->tags->pluck('id')->all());
    }
}
