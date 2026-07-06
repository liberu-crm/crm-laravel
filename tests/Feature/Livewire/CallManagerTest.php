<?php

namespace Tests\Feature\Livewire;

use App\Livewire\CallManager;
use App\Models\Contact;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CallManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_mounts_and_renders_for_a_contact(): void
    {
        $contact = Contact::factory()->create();

        Livewire::test(CallManager::class, ['contactId' => $contact->id])
            ->assertStatus(200)
            ->assertSet('contactId', $contact->id)
            ->assertSet('status', 'idle');
    }

    public function test_initiate_call_sets_sid_status_and_logs_the_call(): void
    {
        $contact = Contact::factory()->create(['phone_number' => '+1234567890']);

        $this->mock(TwilioService::class, function ($mock) use ($contact): void {
            $mock->shouldReceive('initiateCall')
                ->once()
                ->with($contact->phone_number)
                ->andReturn((object) ['sid' => 'CA1234567890abcdef']);

            $mock->shouldReceive('logCall')
                ->once()
                ->with('CA1234567890abcdef', $contact->id, 'outbound', null, 'initiated')
                ->andReturn(new \App\Models\CallLog);
        });

        Livewire::test(CallManager::class, ['contactId' => $contact->id])
            ->call('initiateCall')
            ->assertSet('callSid', 'CA1234567890abcdef')
            ->assertSet('status', 'initiating')
            ->assertHasNoErrors()
            ->assertDispatched('callInitiated', sid: 'CA1234567890abcdef');
    }

    public function test_initiate_call_errors_when_contact_is_missing(): void
    {
        // No shouldReceive: the service must never be called when the contact is gone.
        $this->mock(TwilioService::class);

        Livewire::test(CallManager::class, ['contactId' => 999999])
            ->call('initiateCall')
            ->assertHasErrors('call')
            ->assertSet('callSid', null)
            ->assertSet('status', 'idle');
    }

    public function test_initiate_call_errors_when_twilio_returns_nothing(): void
    {
        $contact = Contact::factory()->create(['phone_number' => '+1234567890']);

        $this->mock(TwilioService::class, function ($mock): void {
            $mock->shouldReceive('initiateCall')->once()->andReturn(null);
            $mock->shouldNotReceive('logCall');
        });

        Livewire::test(CallManager::class, ['contactId' => $contact->id])
            ->call('initiateCall')
            ->assertHasErrors('call')
            ->assertSet('callSid', null);
    }

    public function test_end_call_updates_status_and_clears_sid(): void
    {
        $contact = Contact::factory()->create();

        $this->mock(TwilioService::class, function ($mock): void {
            $mock->shouldReceive('endCall')
                ->once()
                ->with('CA1234567890abcdef')
                ->andReturn(true);
        });

        Livewire::test(CallManager::class, ['contactId' => $contact->id])
            ->set('callSid', 'CA1234567890abcdef')
            ->call('endCall')
            ->assertSet('status', 'ended')
            ->assertSet('callSid', null)
            ->assertHasNoErrors();
    }

    public function test_end_call_errors_when_no_active_call(): void
    {
        $contact = Contact::factory()->create();

        $this->mock(TwilioService::class);

        Livewire::test(CallManager::class, ['contactId' => $contact->id])
            ->call('endCall')
            ->assertHasErrors('call');
    }
}
