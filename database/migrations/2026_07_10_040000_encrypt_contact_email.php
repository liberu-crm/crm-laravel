<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Support\PiiEncryptionBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encrypt Contact.email at rest. The plaintext unique index can't survive
 * encryption (random IV), so uniqueness + equality lookups move to a
 * deterministic email_hash blind index. Existing rows are encrypted + hashed
 * in place. See App\Support\PiiEncryptionBackfill + Contact::hashEmail.
 */
return new class extends Migration
{
    public function up(): void
    {
        // All indexes on email must go before widening to text (MySQL rejects a
        // TEXT column in a key without a length). Lookups move to email_hash.
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropUnique(['email']);
            $table->dropIndex('contacts_email_index');
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->text('email')->change();
            $table->string('email_hash', 64)->nullable()->after('email');
        });

        PiiEncryptionBackfill::encryptColumn(
            'contacts',
            'email',
            'email_hash',
            fn (string $email): string => Contact::hashEmail($email),
        );

        // The blind index inherits the email uniqueness the plaintext column had.
        Schema::table('contacts', function (Blueprint $table): void {
            $table->unique('email_hash');
        });
    }

    public function down(): void
    {
        // Irreversible for the encrypted values; just drop the blind index.
        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropUnique(['email_hash']);
            $table->dropColumn('email_hash');
        });
    }
};
