<?php

declare(strict_types=1);

namespace App\Inbox\Events;

use App\Inbox\InboxChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Inertia\Inertia;
use Inertia\LiveProp;

use function Illuminate\Support\defer;

/**
 * Tells someone's open pages that their inbox changed, so the unread counts
 * and the inbox reload. Nothing about the change itself is sent.
 */
final class InboxChanged implements ShouldBroadcastNow, ShouldRescue
{
    use InteractsWithSockets;

    public const string NAME = 'inbox.changed';

    public function __construct(
        public readonly string $userId,
    ) {}

    /**
     * Broadcasts once the request, job or command has finished, once per
     * person however many changes it made. The tab that made the change
     * already reloads it, so it is left out.
     */
    public static function later(string $userId): void
    {
        defer(fn () => broadcast(new self($userId))->toOthers(), 'inbox-changed:'.$userId);
    }

    /**
     * A page prop about someone's inbox that reloads whenever it changes.
     *
     * @param  callable(): mixed  $value
     */
    public static function liveFor(string $userId, callable $value, ?int $throttle = null): LiveProp
    {
        return Inertia::live($value, on: self::class, channel: InboxChannel::for($userId), throttle: $throttle);
    }

    public function broadcastOn(): PrivateChannel
    {
        return InboxChannel::for($this->userId);
    }

    public function broadcastAs(): string
    {
        return self::NAME;
    }

    /**
     * @return array<string, never>
     */
    public function broadcastWith(): array
    {
        return [];
    }
}
