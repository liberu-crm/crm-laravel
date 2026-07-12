<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Encrypts a plaintext column in place for existing rows, and optionally
 * maintains a deterministic blind-index column alongside it. Idempotent — a
 * value that already decrypts is skipped, so it is safe to re-run (e.g. after an
 * interrupted deploy, or on both slices of the PII-encryption epic). Reads the
 * raw value via the query builder so the model's `encrypted` cast doesn't
 * interfere.
 */
class PiiEncryptionBackfill
{
    /**
     * @param  (callable(string): string)|null  $hasher  computes the blind index
     */
    public static function encryptColumn(string $table, string $column, ?string $hashColumn = null, ?callable $hasher = null): void
    {
        foreach (DB::table($table)->select('id', $column)->cursor() as $row) {
            $value = $row->{$column};

            if (! is_string($value) || $value === '' || self::isEncrypted($value)) {
                continue;
            }

            $update = [$column => Crypt::encryptString($value)];
            if ($hashColumn !== null && $hasher !== null) {
                $update[$hashColumn] = $hasher($value);
            }

            DB::table($table)->where('id', $row->id)->update($update);
        }
    }

    private static function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
