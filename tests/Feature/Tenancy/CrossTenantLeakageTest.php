<?php

namespace Tests\Feature\Tenancy;

use App\Models\Team;
use App\Support\TenantContext;
use App\Traits\IsTenantModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

/**
 * F2: every model tagged tenant-scoped must actually filter by team_id.
 *
 * Layer A (dataprovider, one case per model): the table carries team_id AND a
 * query built under an active tenant context includes the team_id predicate —
 * proves no model silently escapes the global scope.
 *
 * Layer B: real rows in two teams; a tenant must never read the other's rows.
 * Runs for every model with a usable factory (skips the rest, but reports what
 * it actually exercised so coverage is never silently zero).
 */
class CrossTenantLeakageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    /** @return array<string, array{class-string}> */
    public static function tenantModels(): array
    {
        // Filesystem walk — no container helpers: data providers run at
        // collection time, before the app is booted.
        $modelDir = dirname(__DIR__, 3).'/app/Models';
        $models = [];

        foreach (glob($modelDir.'/*.php') as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }
            if (! in_array(IsTenantModel::class, class_uses_recursive($class), true)) {
                continue;
            }
            if (! (new ReflectionClass($class))->isInstantiable()) {
                continue;
            }

            $models[class_basename($class)] = [$class];
        }

        ksort($models);

        return $models;
    }

    #[DataProvider('tenantModels')]
    public function test_model_is_team_scoped(string $class): void
    {
        $table = (new $class)->getTable();

        if (! Schema::hasTable($table)) {
            // Tenant-scoped model with no table = dead/pending code; it holds
            // no rows so it cannot leak. Flag as skipped so the drift stays
            // visible instead of passing green.
            $this->markTestSkipped("{$class}: no `{$table}` table (schema drift — model tagged tenant-scoped but never migrated)");
        }

        $this->assertTrue(
            Schema::hasColumn($table, 'team_id'),
            "{$class}: table `{$table}` has no team_id column — global scope would fatal or leak",
        );

        TenantContext::set(1);
        $sql = $class::query()->toSql();
        TenantContext::clear();

        $this->assertStringContainsString(
            "\"{$table}\".\"team_id\"",
            $sql,
            "{$class}: query under a tenant context is not filtered by team_id",
        );
    }

    public function test_factoried_models_do_not_leak_rows_across_teams(): void
    {
        $teamA = Team::factory()->create();
        $teamB = Team::factory()->create();
        $covered = [];

        foreach (array_keys(self::tenantModels()) as $basename) {
            $class = 'App\\Models\\'.$basename;

            try {
                $a = $class::factory()->create(['team_id' => $teamA->id]);
                $b = $class::factory()->create(['team_id' => $teamB->id]);
            } catch (\Throwable) {
                continue; // no factory, or factory needs FKs we don't set up here
            }

            $key = $a->getKeyName();

            TenantContext::set($teamA->id);
            $visible = $class::pluck($key);
            TenantContext::clear();

            $this->assertTrue(
                $visible->contains($a->getKey()),
                "{$class}: team A cannot see its own row",
            );
            $this->assertFalse(
                $visible->contains($b->getKey()),
                "{$class}: team A leaked team B's row",
            );

            $covered[] = $basename;
        }

        $this->assertNotEmpty($covered, 'no tenant-scoped model had a usable factory to leak-test');
        fwrite(STDERR, "\n[F2] data-level leak-proofed: ".implode(', ', $covered)."\n");
    }
}
