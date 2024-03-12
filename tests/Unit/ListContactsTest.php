<?php

namespace Tests\Unit;

use App\Filament\Admin\Resources\ContactResource\Pages\ListContacts;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ListContactsTest extends TestCase
{
    use RefreshDatabase;

    public function testCreatedAtFilter()
    {
        Contact::factory()->count(5)->create(['created_at' => now()->subDays(10)]);
        Contact::factory()->count(3)->create(['created_at' => now()->subDays(5)]);

        $response = $this->get(route('contacts.list', ['created_at' => now()->subDays(7)]));
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->count() === 3;
        });
    }

    public function testBulkDeleteAction()
    {
        $contacts = Contact::factory()->count(5)->create();
        $deleteIds = $contacts->pluck('id')->toArray();

        $response = $this->delete(route('contacts.bulk.delete', ['ids' => $deleteIds]));
        $this->assertDatabaseMissing('contacts', ['id' => $deleteIds]);
    }

    public function testGlobalSearchFunctionality()
    {
        Contact::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
        Contact::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com']);

        $response = $this->get(route('contacts.list', ['search' => 'John']));
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->count() === 1 && $contacts->first()->name === 'John Doe';
        });

        $response = $this->get(route('contacts.list', ['search' => 'example.com']));
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->count() === 2;
        });

        $response = $this->get(route('contacts.list', ['search' => 'Nonexistent']));
        $response->assertViewHas('contacts', function ($contacts) {
            return $contacts->isEmpty();
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware('web')->group(function () {
            Route::get('/contacts', [ListContacts::class, 'index'])->name('contacts.list');
            Route::delete('/contacts/bulk/delete', [ListContacts::class, 'bulkDelete'])->name('contacts.bulk.delete');
        });
    }
}
