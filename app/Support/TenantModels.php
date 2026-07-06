<?php

declare(strict_types=1);

namespace App\Support;

use App\Traits\IsTenantModel;
use ReflectionClass;

/**
 * Single source of truth for "which Eloquent models are team-scoped."
 *
 * Discovers every instantiable model in app/Models that uses IsTenantModel.
 * Path-relative (not container-dependent) so it works both at runtime and at
 * PHPUnit collection time, before the app boots. CrossTenantLeakageTest and
 * TeamBackupService both consume this — so the test that proves no model
 * escapes the tenant scope also proves the backup covers every model.
 */
class TenantModels
{
    /**
     * @return list<class-string>
     */
    public static function all(): array
    {
        $dir = dirname(__DIR__).'/Models';
        $models = [];

        foreach (glob($dir.'/*.php') ?: [] as $file) {
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

            $models[class_basename($class)] = $class;
        }

        ksort($models);

        /** @var list<class-string> */
        return array_values($models);
    }
}
