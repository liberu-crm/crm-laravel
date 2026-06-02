<?php

declare(strict_types=1);

namespace App\Modules\Contracts;

interface ModuleInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function getDescription(): string;

    public function getDependencies(): array;

    public function isEnabled(): bool;

    public function enable(): void;

    public function disable(): void;

    public function install(): void;

    public function uninstall(): void;

    public function getConfig(): array;

    /**
     * @return array{status: string, checks: array<string, mixed>}
     */
    public function checkHealth(): array;
}
