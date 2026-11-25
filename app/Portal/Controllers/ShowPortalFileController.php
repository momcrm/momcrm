<?php

declare(strict_types=1);

namespace App\Portal\Controllers;

use App\Attachment\Actions\CreateAttachment;
use App\Attachment\Actions\FindAttachment;
use App\Portal\Actions\FindPortalTickets;
use App\Portal\PortalContact;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Downloads a file shared with the customer on a ticket the contact may see.
 * Like staff downloads, it is always a download and never shown inline.
 */
final class ShowPortalFileController
{
    public function __invoke(string $attachment): StreamedResponse
    {
        $contact = PortalContact::current();
        $found = FindAttachment::sharedByIdForCustomer($attachment, $contact->getTeamId()->toString(), $contact->getCustomerId()->toString());
        $path = $found->getPath()->toString();

        if ($found->isNull() || ! Storage::disk(CreateAttachment::disk())->exists($path)) {
            throw new NotFoundHttpException;
        }

        FindPortalTickets::visibleToContact($found->getAttachableId()->toString());

        $response = Storage::disk(CreateAttachment::disk())->download($path, $found->getName()->toString());
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
