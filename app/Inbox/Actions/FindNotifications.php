<?php

declare(strict_types=1);

namespace App\Inbox\Actions;

use App\Inbox\InboxKindEnum;
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
        return self::query($user)
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
        $counts = self::query($user)
            ->whereNull('read_at')
            ->get(['data'])
            ->countBy(fn (DatabaseNotification $notification): string => (string) ($notification->data['kind'] ?? ''));

        return [
            'all' => $counts->sum(),
            ...collect(InboxKindEnum::cases())->mapWithKeys(fn (InboxKindEnum $kind): array => [$kind->value => (int) $counts->get($kind->value, 0)])->all(),
        ];
    }

    public static function countUnread(UserEloquentModel $user): int
    {
        return self::query($user)->whereNull('read_at')->count();
    }

    public static function byIdForUser(UserEloquentModel $user, string $id): ?DatabaseNotification
    {
        return self::query($user)->whereKey($id)->first();
    }

    /**
     * @return Builder<DatabaseNotification>
     */
    public static function query(UserEloquentModel $user): Builder
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->where('data->team_id', $user->current_team_id);
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
            'url' => ($data['subject_type'] ?? null) === 'project'
                ? route('projects.show', (string) $data['subject_id'])
                : route('tickets.index', ['ticket' => (string) ($data['subject_id'] ?? '')]),
        ];
    }
}
