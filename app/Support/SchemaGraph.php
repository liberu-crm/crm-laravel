<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Introspects declared foreign keys from the live schema so a cross-env import
 * can remap them without a hand-maintained edge map (which drifts). Uses
 * Laravel's cross-database Schema::getForeignKeys() (PRAGMA on sqlite,
 * information_schema on MySQL).
 */
class SchemaGraph
{
    /**
     * @param  list<string>  $tables
     * @return array<string, array<string, string>> table => [column => referenced table]
     */
    public static function edges(array $tables): array
    {
        $map = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach (Schema::getForeignKeys($table) as $fk) {
                $columns = $fk['columns'] ?? [];
                $referenced = $fk['foreign_table'] ?? null;
                if ($referenced === null) {
                    continue;
                }

                // Only single-column FKs are remappable row-by-row; the schema
                // has no composite FKs among team-scoped tables.
                if (count($columns) === 1) {
                    $map[$table][$columns[0]] = $referenced;
                }
            }
        }

        return $map;
    }
}
