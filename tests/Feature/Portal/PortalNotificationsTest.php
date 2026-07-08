<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Portal\ShareDocumentWithContact;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Notifications\DocumentSharedNotification;
use App\Services\DocumentService;
use Database\Seeders\RolesSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PortalNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function share(Contact $contact): void
    {
        (new ShareDocumentWithContact(app(DocumentService::class)))(
            $contact,
            UploadedFile::fake()->image('doc.png'),
            'Shared file',
            'report',
        );
    }

    private function portalCustomer(string $email, Team $team): User
    {
        $customer = User::factory()->create(['email' => $email, 'email_verified_at' => now()]);
        $customer->forceFill(['current_team_id' => $team->id])->save();
        setPermissionsTeamId(null);
        $customer->assignRole('customer');

        return $customer;
    }

    public function test_portal_panel_shows_the_notification_bell(): void
    {
        $this->assertTrue(Filament::getPanel('portal')->hasDatabaseNotifications());
    }

    public function test_sharing_a_document_notifies_the_portal_customer(): void
    {
        Storage::fake();
        Notification::fake();
        $this->seed(RolesSeeder::class);
        $team = Team::factory()->create();
        $customer = $this->portalCustomer('cust@example.com', $team);
        $contact = Contact::factory()->create(['team_id' => $team->id, 'email' => 'cust@example.com']);

        $this->share($contact);

        Notification::assertSentTo($customer, DocumentSharedNotification::class);
    }

    public function test_sharing_with_a_non_customer_contact_notifies_no_one(): void
    {
        Storage::fake();
        Notification::fake();
        $this->seed(RolesSeeder::class);
        $team = Team::factory()->create();
        $contact = Contact::factory()->create(['team_id' => $team->id, 'email' => 'not-a-customer@example.com']);

        $this->share($contact);

        Notification::assertNothingSent();
    }
}
