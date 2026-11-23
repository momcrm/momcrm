<?php

declare(strict_types=1);

namespace App\Inbox\Actions;

use App\Inbox\InboxKindEnum;
use App\Inbox\InboxNotifications;
use App\Shared\Types;
use App\User\UserEloquentModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * Someone's in-app notifications in their current team.
 */
final class FindNotifications
{
    public const int LIMIT = 100;

    /**
     * Newest first, optionally of one kind or only unread ones.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public static function forUser(UserEloquentModel $user, ?InboxKindEnum $kind = null, bool $unreadOnly = false): Collection
    {
        return InboxNotifications::query($user)
            ->when($kind, fn (Builder $query, InboxKindEnum $kind) => $query->where('data->kind', $kind->value))
            ->when($unreadOnly, fn (Builder $query) => $query->whereNull('read_at'))
            ->latest()
            ->limit(self::LIMIT)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => self::listItem($notification))
            ->values();
    }

    /**
     * Unread notifications per kind, plus the total.
     *
     * @return array<string, int>
     */
    public static function unreadCounts(UserEloquentModel $user): array
    {
        $counts = InboxNotifications::query($user)
            ->whereNull('read_at')
            ->get(['data'])
            ->countBy(fn (DatabaseNotification $notification): string => Types::string($notification->data['kind'] ?? ''));

        return [
            'all' => Types::intOrZero($counts->sum()),
            ...collect(InboxKindEnum::cases())->mapWithKeys(fn (InboxKindEnum $kind): array => [$kind->value => (int) $counts->get($kind->value, 0)])->all(),
        ];
    }

    public static function countUnread(UserEloquentModel $user): int
    {
        return InboxNotifications::query($user)->whereNull('read_at')->count();
    }

    public static function existsForUser(UserEloquentModel $user, string $id): bool
    {
        return InboxNotifications::query($user)->whereKey($id)->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private static function listItem(DatabaseNotification $notification): array
    {
        /** @var array<string, mixed> $data */
        $data = $notification->data;

        return [
            ...$data,
            'id' => $notification->id,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
            'url' => match ($data['subject_type'] ?? null) {
                'project' => route('projects.show', Types::string($data['subject_id'])),
                'shift' => route('attendance.time'),
                'leave' => route('attendance.leave'),
                'record' => route('records.show', [Types::string($data['object_id'] ?? ''), Types::string($data['subject_id'])]),
                default => route('tickets.index', ['ticket' => Types::string($data['subject_id'] ?? '')]),
            },
        ];
    }
}
