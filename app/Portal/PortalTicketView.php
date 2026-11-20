<?php

declare(strict_types=1);

namespace App\Portal;

use App\Attachment\Attachment;
use App\Ticket\Ticket;

/**
 * How a ticket and its files look to a contact in the portal: no staff-only
 * details, such as who it's assigned to or its internal notes.
 */
final class PortalTicketView
{
    /**
     * @param  array<string, array{name: string, hue: ?int, is_closed: bool}>  $statuses
     * @return array{id: string, number: int, title: string, status: array{name: string, hue: ?int, is_closed: bool}, raised_by: ?string, created_at: ?string, updated_at: ?string}
     */
    public static function item(Ticket $ticket, array $statuses): array
    {
        return [
            'id' => $ticket->getId()->toString(),
            'number' => $ticket->getNumber()->toInteger(),
            'title' => $ticket->getTitle()->toString(),
            'status' => $statuses[$ticket->getStatusId()->toString()] ?? ['name' => '', 'hue' => null, 'is_closed' => false],
            'raised_by' => $ticket->getRequesterName()->toNullableString(),
            'created_at' => $ticket->getCreatedAt()->toNullableISOString(),
            'updated_at' => $ticket->getUpdatedAt()->toNullableISOString(),
        ];
    }

    /**
     * @return array{id: string, name: string, size: int, mime_type: string, created_at: ?string, url: string}
     */
    public static function file(Attachment $attachment): array
    {
        return [
            'id' => $attachment->getId()->toString(),
            'name' => $attachment->getName()->toString(),
            'size' => $attachment->getSize()->toInteger(),
            'mime_type' => $attachment->getMimeType()->toString(),
            'created_at' => $attachment->getCreatedAt()->toNullableISOString(),
            'url' => route('portal.files.show', $attachment->getId()->toString()),
        ];
    }
}
