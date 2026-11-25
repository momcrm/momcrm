<?php

declare(strict_types=1);

namespace App\Inbox;

use App\Shared\Types;

/**
 * Where a notification leads: the ticket, record, project or page it is
 * about.
 */
final class InboxLink
{
    /**
     * @param  array<string, mixed>  $data  a notification's stored payload
     */
    public static function for(array $data): string
    {
        return match ($data['subject_type'] ?? null) {
            'project' => route('projects.show', Types::string($data['subject_id'])),
            'shift' => route('attendance.time'),
            'leave' => route('attendance.leave'),
            'timesheet' => route('attendance.timesheet', [Types::string($data['member_id'] ?? ''), mb_substr(Types::string($data['starts_on'] ?? ''), 0, 10)]),
            'record' => route('records.show', [Types::string($data['object_id'] ?? ''), Types::string($data['subject_id'])]),
            default => route('tickets.index', ['ticket' => Types::string($data['subject_id'] ?? '')]),
        };
    }
}
