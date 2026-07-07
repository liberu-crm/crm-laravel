<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Filament\Portal\Resources\DocumentResource\Pages\ListDocuments;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PortalDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private Team $team;

    private User $customer;

    /** Signs in a customer whose email matches a Contact in their team; returns that Contact. */
    private function actingCustomer(string $email = 'cust@example.com'): Contact
    {
        $this->seed(RolesSeeder::class);
        $this->team = Team::factory()->create();
        $this->customer = User::factory()->create(['email' => $email, 'email_verified_at' => now()]);
        $this->customer->forceFill(['current_team_id' => $this->team->id])->save();
        setPermissionsTeamId(null);
        $this->customer->assignRole('customer');
        $this->actingAs($this->customer);
        Filament::setCurrentPanel(Filament::getPanel('portal'));

        return Contact::factory()->create(['team_id' => $this->team->id, 'email' => $email]);
    }

    private function documentFor(Contact $contact, array $overrides = []): Document
    {
        return Document::factory()->create(array_merge([
            'team_id' => $contact->team_id,
            'documentable_type' => Contact::class,
            'documentable_id' => $contact->id,
        ], $overrides));
    }

    public function test_lists_only_own_contacts_documents(): void
    {
        $contact = $this->actingCustomer();
        $mine = $this->documentFor($contact, ['name' => 'My contract']);

        $otherContact = Contact::factory()->create(['team_id' => $this->team->id, 'email' => 'other@example.com']);
        $otherDoc = $this->documentFor($otherContact, ['name' => 'Someone else']);

        $foreignContact = Contact::factory()->create(); // different team
        $foreignDoc = $this->documentFor($foreignContact, ['team_id' => $foreignContact->team_id]);

        Livewire::test(ListDocuments::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$otherDoc, $foreignDoc]);
    }

    public function test_customer_without_matching_contact_sees_nothing(): void
    {
        $contact = $this->actingCustomer();
        $otherContact = Contact::factory()->create(['team_id' => $this->team->id, 'email' => 'x@example.com']);
        $doc = $this->documentFor($otherContact);
        $contact->delete(); // now no contact matches the customer's email

        Livewire::test(ListDocuments::class)
            ->assertCanNotSeeTableRecords([$doc]);
    }

    public function test_customer_downloads_own_document(): void
    {
        Storage::fake();
        $contact = $this->actingCustomer();
        $doc = $this->documentFor($contact, [
            'file_path' => 'documents/contract.pdf',
            'original_filename' => 'contract.pdf',
        ]);
        Storage::put('documents/contract.pdf', 'binary');

        Livewire::test(ListDocuments::class)
            ->callTableAction('download', $doc)
            ->assertFileDownloaded('contract.pdf');
    }
}
