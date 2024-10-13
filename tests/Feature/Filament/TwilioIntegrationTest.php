<?php

namespace Tests\Feature\Filament;

use App\Filament\App\Pages\TwilioIntegration;
use App\Models\Contact;
use App\Models\Lead;
use App\Services\TwilioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TwilioIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(TwilioService::class);
    }

    /** @test */
    public function it_can_send_single_sms()
    {
        $contact = Contact::factory()->create(['phone_number' => '+1234567890']);

        $this->mock(TwilioService::class)
            ->shouldReceive('sendSMS')
            ->once()
            ->with('+1234567890', 'Test message')
            ->andReturn(true);

        Livewire::test(TwilioIntegration::class)
            ->set('to', $contact->phone_number)
            ->set('message', 'Test message')
            ->call('sendSMS')
            ->assertHasNoErrors()
            ->assertDispatched('notify');
    }

    /** @test */
    public function it_can_initiate_single_call()
    {
        $contact = Contact::factory()->create(['phone_number' => '+1234567890']);

        $this->mock(TwilioService::class)
            ->shouldReceive('makeCall')
            ->once()
            ->with('+1234567890', \Mockery::any())
            ->andReturn(true);

        Livewire::test(TwilioIntegration::class)
            ->set('to', $contact->phone_number)
            ->call('makeCall')
            ->assertHasNoErrors()
            ->assertDispatched('notify');
    }

    /** @test */
    public function it_can_send_bulk_sms()
    {
        $contacts = Contact::factory()->count(3)->create();

        $this->mock(TwilioService::class)
            ->shouldReceive('sendSMS')
            ->times(3)
            ->andReturn(true);

        Livewire::test(TwilioIntegration::class)
            ->set('message', 'Bulk test message')
            ->call('bulkSendSMS', $contacts->pluck('id')->toArray())
            ->assertHasNoErrors()
            ->assertDispatched('notify');
    }

    /** @test */
    public function it_can_initiate_bulk_calls()
    {
        $leads = Lead::factory()->count(3)->create();

        $this->mock(TwilioService::class)
            ->shouldReceive('makeCall')
            ->times(3)
            ->andReturn(true);

        Livewire::test(TwilioIntegration::class)
            ->call('bulkMakeCall', $leads->pluck('id')->toArray())
            ->assertHasNoErrors()
            ->assertDispatched('notify');
    }

    /** @test */
    public function it_can_update_twilio_settings()
    {
        Livewire::test(TwilioIntegration::class)
            ->set('sid', 'new_sid')
            ->set('auth_token', 'new_auth_token')
            ->set('phone_number', '+1987654321')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertEquals('new_sid', config('services.twilio.sid'));
        $this->assertEquals('new_auth_token', config('services.twilio.auth_token'));
        $this->assertEquals('+1987654321', config('services.twilio.phone_number'));
    }
}