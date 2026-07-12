<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Contact;
use App\Support\PiiEncryptionBackfill;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContactEmailEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_is_encrypted_at_rest(): void
    {
        $contact = Contact::factory()->create(['email' => 'jane@example.com']);

        $raw = $contact->getRawOriginal('email');
        $this->assertNotSame('jane@example.com', $raw);
        $this->assertSame('jane@example.com', Crypt::decryptString($raw));
        $this->assertSame('jane@example.com', $contact->fresh()->email);
    }

    public function test_email_hash_is_maintained_and_case_insensitive(): void
    {
        $contact = Contact::factory()->create(['email' => 'Jane@Example.com']);

        $this->assertSame(Contact::hashEmail('jane@example.com'), $contact->email_hash);
        $this->assertSame(Contact::hashEmail('JANE@EXAMPLE.COM'), $contact->email_hash);
    }

    public function test_lookup_by_blind_index_finds_the_contact(): void
    {
        $contact = Contact::factory()->create(['email' => 'lookup@corp.example']);

        $found = Contact::where('email_hash', Contact::hashEmail('lookup@corp.example'))->first();

        $this->assertNotNull($found);
        $this->assertTrue($contact->is($found));
    }

    public function test_duplicate_email_violates_uniqueness(): void
    {
        Contact::factory()->create(['email' => 'dupe@corp.example']);

        $this->expectException(QueryException::class);
        Contact::factory()->create(['email' => 'dupe@corp.example']);
    }

    public function test_backfill_encrypts_a_plaintext_row_and_is_idempotent(): void
    {
        // A pre-encryption row: raw plaintext email + no hash (bypass the cast).
        $id = DB::table('contacts')->insertGetId([
            'name' => 'Legacy',
            'email' => 'legacy@corp.example',
            'phone_number' => '123',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PiiEncryptionBackfill::encryptColumn('contacts', 'email', 'email_hash', fn (string $e): string => Contact::hashEmail($e));

        $row = DB::table('contacts')->where('id', $id)->first();
        $this->assertSame('legacy@corp.example', Crypt::decryptString($row->email));
        $this->assertSame(Contact::hashEmail('legacy@corp.example'), $row->email_hash);

        // Re-running must not double-encrypt.
        PiiEncryptionBackfill::encryptColumn('contacts', 'email', 'email_hash', fn (string $e): string => Contact::hashEmail($e));
        $again = DB::table('contacts')->where('id', $id)->first();
        $this->assertSame('legacy@corp.example', Crypt::decryptString($again->email));
    }
}
