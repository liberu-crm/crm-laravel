<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Company;
use App\Models\Contact;
use App\Support\PiiEncryptionBackfill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhoneEncryptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_phone_is_encrypted_at_rest(): void
    {
        $contact = Contact::factory()->create(['phone_number' => '+1-555-0100']);

        $raw = $contact->getRawOriginal('phone_number');
        $this->assertNotSame('+1-555-0100', $raw);
        $this->assertSame('+1-555-0100', Crypt::decryptString($raw));
        $this->assertSame('+1-555-0100', $contact->fresh()->phone_number);
    }

    public function test_company_phone_is_encrypted_at_rest(): void
    {
        $company = Company::factory()->create(['phone_number' => '+1-555-0199']);

        $raw = $company->getRawOriginal('phone_number');
        $this->assertNotSame('+1-555-0199', $raw);
        $this->assertSame('+1-555-0199', Crypt::decryptString($raw));
        $this->assertSame('+1-555-0199', $company->fresh()->phone_number);
    }

    public function test_backfill_encrypts_an_existing_plaintext_phone(): void
    {
        $id = DB::table('companies')->insertGetId([
            'name' => 'Legacy Co',
            'phone_number' => '+1-555-7777',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        PiiEncryptionBackfill::encryptColumn('companies', 'phone_number');

        $row = DB::table('companies')->where('id', $id)->first();
        $this->assertSame('+1-555-7777', Crypt::decryptString($row->phone_number));
    }
}
