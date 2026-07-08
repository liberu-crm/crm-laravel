<?php

declare(strict_types=1);

namespace App\Actions\Portal;

use App\Models\Contact;
use App\Models\Document;
use App\Notifications\DocumentSharedNotification;
use App\Services\DocumentService;
use App\Support\PortalCustomer;
use Illuminate\Http\UploadedFile;

/**
 * Attaches a staff-uploaded file to a Contact so it surfaces in that customer's
 * portal document browse (#484), which filters team_id + documentable=Contact and
 * displays name/type. Reuses DocumentService::upload for the mime-allowlist check
 * and disk/path convention download reads back; upload() omits team_id/name/type
 * (Document is not IsTenantModel, so nothing auto-stamps them) — patch them here.
 */
class ShareDocumentWithContact
{
    public function __construct(private DocumentService $documents) {}

    public function __invoke(Contact $contact, UploadedFile $file, string $name, ?string $type = null): Document
    {
        $document = $this->documents->upload($file, $contact, ['title' => $name]);

        $document->forceFill([
            'team_id' => $contact->getAttribute('team_id'),
            'name' => $name,
            'type' => $type,
        ])->save();

        // Tell the customer, if this Contact has a portal account. Null-safe:
        // a Contact with no portal user notifies no one.
        PortalCustomer::forEmail($contact->getAttribute('email'))
            ?->notify(new DocumentSharedNotification($document));

        return $document;
    }
}
