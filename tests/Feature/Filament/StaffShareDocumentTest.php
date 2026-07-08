<?php

declare(strict_types=1);

namespace Tests\Feature\Filament;

use App\Actions\Portal\ShareDocumentWithContact;
use App\Filament\App\Resources\ContactResource\Pages\EditContact;
use App\Filament\App\Resources\ContactResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Portal\Resources\DocumentResource as PortalDocumentResource;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Team;
use App\Models\User;
use App\Services\DocumentService;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class StaffShareDocumentTest extends TestCase
{
    use RefreshDatabase;

    private function share(Contact $contact, string $name = 'Q3 Report', string $type = 'report'): Document
    {
        return (new ShareDocumentWithContact(app(DocumentService::class)))(
            $contact,
            UploadedFile::fake()->image('doc.png'),
            $name,
            $type,
        );
    }

    private function actAsPortalCustomer(string $email, Team $team): void
    {
        $customer = User::factory()->create(['email' => $email, 'email_verified_at' => now()]);
        $customer->forceFill(['current_team_id' => $team->id])->save();
        setPermissionsTeamId(null);
        $customer->assignRole('customer');
        $this->actingAs($customer);
        Filament::setCurrentPanel(Filament::getPanel('portal'));
    }

    public function test_sharing_creates_a_portal_visible_document_row(): void
    {
        Storage::fake();
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id, 'email' => 'cust@example.com']);

        $doc = $this->share($contact);

        $this->assertDatabaseHas('documents', [
            'id' => $doc->id,
            'team_id' => $team->id,
            'documentable_type' => Contact::class,
            'documentable_id' => $contact->id,
            'name' => 'Q3 Report',
            'type' => 'report',
        ]);
        $this->assertNotNull($doc->getAttribute('file_path'));
    }

    public function test_shared_document_appears_in_the_portal_browse(): void
    {
        Storage::fake();
        $this->seed(RolesSeeder::class);
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id, 'email' => 'cust@example.com']);
        $doc = $this->share($contact);

        $this->actAsPortalCustomer('cust@example.com', $team);

        $this->assertTrue(PortalDocumentResource::getEloquentQuery()->pluck('id')->contains($doc->id));
    }

    public function test_document_on_another_contact_is_not_visible(): void
    {
        Storage::fake();
        $this->seed(RolesSeeder::class);
        $team = Team::factory()->create();
        $other = Contact::factory()->create(['team_id' => $team->id, 'email' => 'someone-else@example.com']);
        $doc = $this->share($other);

        $this->actAsPortalCustomer('cust@example.com', $team);

        $this->assertFalse(PortalDocumentResource::getEloquentQuery()->pluck('id')->contains($doc->id));
    }

    public function test_staff_can_share_via_the_relation_manager(): void
    {
        Storage::fake();
        $this->seed(RolesSeeder::class);
        $staff = User::factory()->withPersonalTeam()->create(['email_verified_at' => now()]);
        $team = $staff->currentTeam;
        setPermissionsTeamId($team->id);
        $staff->assignRole('manager');
        $this->actingAs($staff);
        Filament::setCurrentPanel(Filament::getPanel('app'));
        Filament::setTenant($team);

        $contact = Contact::factory()->create(['team_id' => $team->id, 'email' => 'cust@example.com']);

        Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $contact,
            'pageClass' => EditContact::class,
        ])->callTableAction('share', data: [
            'file' => UploadedFile::fake()->image('shared.png'),
            'name' => 'Signed contract',
            'type' => 'contract',
        ]);

        $this->assertDatabaseHas('documents', [
            'documentable_id' => $contact->id,
            'documentable_type' => Contact::class,
            'team_id' => $team->id,
            'name' => 'Signed contract',
            'type' => 'contract',
        ]);
    }
}
