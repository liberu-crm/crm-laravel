<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\ImportTeamBackupService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Imports an uploaded backup zip (from another environment) into a new team,
 * off the request cycle. Stages the stored upload to a local temp file (zip
 * reads need a local path), runs the import, notifies the initiating admin.
 */
class ImportTeamBackup implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $disk,
        public string $path,
        public ?string $name,
        public int $importerId,
    ) {}

    public function handle(ImportTeamBackupService $service): void
    {
        $importer = User::findOrFail($this->importerId);

        $tmp = tempnam(sys_get_temp_dir(), 'teamimport');
        file_put_contents($tmp, (string) Storage::disk($this->disk)->get($this->path));

        try {
            $team = $service->import($tmp, $this->name, $importer);
            $this->notify('Import complete', true, "Imported into new team “{$team->name}” (#{$team->id}).", $importer);
        } catch (Throwable $e) {
            $this->notify('Import failed', false, $e->getMessage(), $importer);
            throw $e;
        } finally {
            @unlink($tmp);
        }
    }

    private function notify(string $title, bool $success, string $body, User $user): void
    {
        $notification = Notification::make()->title($title)->body($body);
        ($success ? $notification->success() : $notification->danger())->sendToDatabase($user);
    }
}
